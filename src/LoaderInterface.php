<?php

declare(strict_types=1);

namespace Pander\DataLoaderSupport;

use GraphQL\Executor\Promise\Promise;

interface LoaderInterface {
  public function load(string $entityClass, array $keys, string $keyField = 'id'): Promise;

  public function loadByParent(string $entityClass, string $parentField, array $parentKeys): Promise;

  public function loadByJoinTable(string $owningClass, string $field, array $keys): Promise;
}
