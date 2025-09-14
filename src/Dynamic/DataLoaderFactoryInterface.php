<?php

declare(strict_types=1);

namespace Pander\DataLoaderSupport\Dynamic;

use Overblog\DataLoader\DataLoader;

interface DataLoaderFactoryInterface {
  public function createPrimaryKeyLoader(string $clazz, string $field): DataLoader;

  public function createByParentLoader(string $clazz, string $field, array $parameters = []): DataLoader;

  public function createByJoinTableLoader(string $clazz, string $field, array $parameters = []): DataLoader;
}
