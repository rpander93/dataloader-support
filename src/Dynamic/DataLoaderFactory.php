<?php

declare(strict_types=1);

namespace Pander\DataLoaderSupport\Dynamic;

use Doctrine\ORM\QueryBuilder;
use Overblog\DataLoader\DataLoader;
use Overblog\PromiseAdapter\PromiseAdapterInterface;
use Pander\DataLoaderSupport\LoaderInterface;

class DataLoaderFactory implements DataLoaderFactoryInterface {
  public function __construct(private LoaderInterface $loader, private PromiseAdapterInterface $promiseAdapter) {
  }

  public function createPrimaryKeyLoader(string $clazz, string $field): DataLoader {
    return $this->createDataLoader(function (array $keys) use ($clazz, $field) {
      return $this->loader->load($clazz, $keys, $field);
    });
  }

  public function createByParentLoader(string $clazz, string $field, array $parameters = []): DataLoader {
    $constraints = $this->createConstraints($parameters);

    return $this->createDataLoader(function (array $keys) use ($clazz, $field, $constraints) {
      return $this->loader->loadByParent($clazz, $field, $keys, $constraints);
    });
  }

  public function createByJoinTableLoader(string $clazz, string $field, array $parameters = []): DataLoader {
    $constraints = $this->createConstraints($parameters);

    return $this->createDataLoader(function (array $keys) use ($clazz, $field, $constraints) {
      return $this->loader->loadByJoinTable($clazz, $field, $keys, $constraints);
    });
  }

  private function createDataLoader(callable $fn): DataLoader {
    return new DataLoader($fn, $this->promiseAdapter);
  }

  private function createConstraints(array $parameters): callable {
    return function (QueryBuilder $builder) use ($parameters) {
      foreach ($parameters as $field => $value) {
        if (null === $value) {
          $builder->andWhere($builder->expr()->isNull(\sprintf('x.%s', $field)));
        } else {
          $placeholder = \sprintf(':%s_placeholder', $field);

          $builder->andWhere($builder->expr()->eq(\sprintf('x.%s', $field), $placeholder));
          $builder->setParameter($placeholder, $value);
        }
      }
    };
  }
}
