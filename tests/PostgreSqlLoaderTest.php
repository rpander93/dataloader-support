<?php

declare(strict_types=1);

namespace Tests\Pander\DataLoaderSupport;

use Doctrine\ORM\EntityManager;
use GraphQL\Executor\Promise\Adapter\SyncPromiseAdapter;
use GraphQL\Executor\Promise\Promise;
use Pander\DataLoaderSupport\PostgreSqlLoader;
use PHPUnit\Framework\TestCase;

class PostgreSqlLoaderTest extends TestCase {
  private static EntityManager $entityManager;
  private PostgreSqlLoader $loader;
  private SyncPromiseAdapter $promiseAdapter;

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    $entityManagerFactory = require_once __DIR__.'/createConfiguredDatabase.php';
    static::$entityManager = $entityManagerFactory();
  }

  public function setUp(): void {
    $this->promiseAdapter = new SyncPromiseAdapter();
    $this->loader = new PostgreSqlLoader(static::$entityManager, $this->promiseAdapter);
  }

  public function testLoadsEntitiesInRequestedOrder(): void {
    $availableIds = $this->fetchIdsOrderedRandomly(Entity::class);

    $promise = $this->loader->load(Entity::class, $availableIds);
    $result = $this->waitFor($promise);

    static::assertArraysSameSequence($availableIds, array_column($result, 'id'));
  }

  public function testLoadsEntitiesByJoiningSideOrdered(): void {
    $parentIds = $this->fetchIdsOrderedRandomly(Entity::class);

    $promise = $this->loader->loadByParent(Entity::class, 'parent', $parentIds);
    $result = $this->waitFor($promise);

    static::assertCount(\count($parentIds), $result);
    foreach ($parentIds as $i => $parentId) {
      $slice = $result[$i];
      $resultParentIds = array_column($slice, 'parent_id');

      static::assertArrayContainsOnly($parentId, $resultParentIds);
    }
  }

  public function testLoadsEntitiesByJoinTable(): void {
    $parentIds = $this->fetchIdsOrderedRandomly(Entity::class);
    $connection = static::$entityManager->getConnection();

    $promise = $this->loader->loadByJoinTable(Entity::class, 'joinTable', $parentIds);
    $result = $this->waitFor($promise);

    static::assertCount(\count($parentIds), $result);
    foreach ($parentIds as $i => $parentId) {
      $slice = $result[$i];

      $allowedChildIds = $connection
        ->executeQuery('SELECT right_id FROM entity_entity WHERE left_id = ?', [$parentId])
        ->fetchFirstColumn();

      $returnedChildIds = array_column($slice, 'id');
      static::assertEqualsCanonicalizing($allowedChildIds, $returnedChildIds);
    }
  }

  private function waitFor(Promise $promise) {
    return $this->promiseAdapter->wait($promise);
  }

  private function fetchIdsOrderedRandomly(string $className) {
    $builder = static::$entityManager
      ->createQueryBuilder();
    $builder
      ->select('x')
      ->from($className, 'x')
      ->setMaxResults(5);
    $entities = $builder
      ->getQuery()
      ->getArrayResult();

    $availableIds = array_column($entities, 'id');
    shuffle($availableIds);

    return $availableIds;
  }

  private static function assertArrayContainsOnly($expected, $value) {
    foreach ($value as $val) {
      static::assertSame($expected, $val);
    }
  }

  private static function assertArraysSameSequence($expected, $value) {
    static::assertCount(\count($expected), $value);

    foreach ($expected as $i => $expectedValue) {
      static::assertSame($expectedValue, $value[$i], \sprintf('Array value for key %s is different', $i));
    }
  }
}
