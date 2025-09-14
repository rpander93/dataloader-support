<?php

declare(strict_types=1);

namespace Pander\DataLoaderSupport\Dynamic;

use Overblog\DataLoader\DataLoader;

class MemoizedDataLoaderFactory implements DataLoaderFactoryInterface {
  private $instances = [];

  public function __construct(private DataLoaderFactoryInterface $inner) {
  }

  public function createPrimaryKeyLoader(string $clazz, string $field): DataLoader {
    return $this->createCached([$clazz, $field], fn () => $this->inner->createPrimaryKeyLoader($clazz, $field));
  }

  public function createByParentLoader(string $clazz, string $field, array $parameters = []): DataLoader {
    return $this->createCached([$clazz, $field, ...$parameters], fn () => $this->inner->createByParentLoader($clazz, $field, $parameters));
  }

  public function createByJoinTableLoader(string $clazz, string $field, array $parameters = []): DataLoader {
    return $this->createCached([$clazz, $field, ...$parameters], fn () => $this->inner->createByJoinTableLoader($clazz, $field, $parameters));
  }

  private function createCached(array $key, callable $factory): DataLoader {
    $hash = sha1(serialize($key));

    if (!isset($this->instances[$hash])) {
      $this->instances[$hash] = $factory();
    }

    return $this->instances[$hash];
  }
}
