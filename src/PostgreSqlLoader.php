<?php

declare(strict_types=1);

namespace Pander\DataLoaderSupport;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\Query;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;

class PostgreSqlLoader implements LoaderInterface {
  public function __construct(
    private EntityManagerInterface $entityManager,
    private PromiseAdapter $promiseAdapter,
    /** @param "object"|"array" */
    private string $hydrationMode = 'object',
  ) {
    // ..
  }

  public function setHydrationMode(string $mode): void {
    $this->hydrationMode = $mode;
  }

  public function load(string $entityClass, array $keys, string $keyField = 'id'): Promise {
    $builder = $this->entityManager
      ->createQueryBuilder();
    $builder
      ->select('x')
      ->from($entityClass, 'x')
      ->where($builder->expr()->in(\sprintf('x.%s', $keyField), ':objectIds'))
      ->orderBy(\sprintf('IDX(%s, x.%s)', implode(',', $keys), $keyField))
      ->setParameter('objectIds', $keys, ArrayParameterType::INTEGER);

    $query = $builder->getQuery();
    $elements = $this->runQuery($query);
    $retVal = \count($elements) === \count($keys) ? $elements : $this->zipMissingValues($keys, $elements, $keyField);

    return $this->promiseAdapter->all($retVal);
  }

  public function loadByParent(string $entityClass, string $parentField, array $parentKeys): Promise {
    $classMetadata = $this->entityManager->getClassMetadata($entityClass);
    /** @var ManyToOneAssociationMapping $fieldMapping */
    $fieldMapping = $classMetadata->getAssociationMapping($parentField);
    $matchColumn = $fieldMapping->joinColumns[0]->name;

    $builder = $this
      ->entityManager
      ->createQueryBuilder();
    $builder
      ->select('x')
      ->from($entityClass, 'x')
      ->where($builder->expr()->in(\sprintf('x.%s', $parentField), ':objectIds'))
      ->setParameter('objectIds', $parentKeys, ArrayParameterType::INTEGER);

    $query = $builder->getQuery();
    $elements = $this->runQuery($query);

    $retVal = [];
    foreach ($parentKeys as $elementId) {
      $matches = [];
      foreach ($elements as $i => $element) {
        if ($element[$matchColumn] === $elementId) {
          $matches[] = $element;
          unset($elements[$i]);
        }
      }

      $retVal[] = $matches;
    }

    return $this->promiseAdapter->all($retVal);
  }

  public function loadByJoinTable(string $owningClass, string $field, array $objectIds): Promise {
    $metadata = $this->entityManager->getClassMetadata($owningClass);
    /** @var ManyToManyOwningSideMapping $fieldMapping */
    $fieldMapping = $metadata->getAssociationMapping($field);
    $joinTable = $fieldMapping->joinTable->name;
    $sourceColumn = $fieldMapping->joinTable->joinColumns[0]->name;
    $targetColumn = $fieldMapping->joinTable->inverseJoinColumns[0]->name;
    $targetReferencedColumnName = $fieldMapping->joinTable->inverseJoinColumns[0]->referencedColumnName;
    $targetEntity = $fieldMapping->targetEntity;

    // Fetch list of entities to retrieve
    $builder = $this->entityManager
      ->getConnection()
      ->createQueryBuilder();
    $builder
      ->select(\sprintf('x.%s AS source_column', $sourceColumn), \sprintf('x.%s AS target_column', $targetColumn))
      ->from($joinTable, 'x')
      ->where($builder->expr()->in(\sprintf('x.%s', $sourceColumn), ':objectIds'))
      ->setParameter('objectIds', $objectIds, ArrayParameterType::INTEGER);
    /** @var array{ source_column: int; target_column: int; } */
    $targetSourceMapping = $builder->fetchAllAssociative();
    $targetSelection = array_map(array: $targetSourceMapping, callback: fn (array $x) => $x['target_column']);

    // Actually fetch the entities
    $builder = $this->entityManager
      ->createQueryBuilder();
    $builder
      ->select('x')
      ->from($targetEntity, 'x')
      ->where($builder->expr()->in(\sprintf('x.%s', $targetReferencedColumnName), ':targetIds'))
      ->setParameter('targetIds', $targetSelection, ArrayParameterType::INTEGER);

    $query = $builder->getQuery();
    $results = $this->runQuery($query);
    $propertyAccessor = $this->createPropertyAccessor($targetEntity, $targetReferencedColumnName);
    
    $retVal = [];
    foreach ($objectIds as $objectId) {
      $matches = [];

      foreach ($results as $result) {
        $referencedColumnValue = $propertyAccessor($result, $targetReferencedColumnName);

        foreach ($targetSourceMapping as $mapping) {
          if ($objectId === $mapping['source_column'] && $referencedColumnValue === $mapping['target_column']) {
            $matches[] = $result;

            break;
          }
        }
      }

      $retVal[] = $matches;
    }

    return $this->promiseAdapter->all($retVal);
  }

  // Creates a property accessor that uses `getX()` or `x()` method to access on objects
  // or directly access the property on arrays
  private function createPropertyAccessor(string $owningClass, string $property): \Closure {
    if ($this->hydrationMode === 'array') {
      return function (array $result) use ($property) {
        return $result[$property];
      };
    }

    if (method_exists($owningClass, 'get' . \ucfirst($property))) {
      return function ($result) use ($property) {
        return $result->{'get' . \ucfirst($property)}();
      };
    }

    return function ($result) use ($property) {
      return $result->{$property}();
    };
  }

  private function runQuery(Query $query): array {
    $query->setHint(Query::HINT_INCLUDE_META_COLUMNS, true);

    // Enable translations if extension is available
    if (class_exists(TranslationWalker::class, false)) {
      $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
      $query->setHint(\Gedmo\Translatable\TranslatableListener::HINT_FALLBACK, 1);
    }

    if ('object' === $this->hydrationMode) {
      return $query->getResult();
    }

    return $query->getArrayResult();
  }

  private function zipMissingValues(array $objectIds, array $results, string $field): array {
    $retVal = [];

    $resultsCount = \count($results);
    $indexOffset = 0;
    foreach ($objectIds as $index => $objectId) {
      $currentElementIndex = $index + $indexOffset;
      $currentElement = $currentElementIndex < $resultsCount ? $results[$currentElementIndex] : null;

      if (null === $currentElement || $currentElement[$field] !== $objectId) {
        $retVal[] = null;
        --$indexOffset;
      } else {
        $retVal[] = $currentElement;
      }
    }

    return $retVal;
  }
}
