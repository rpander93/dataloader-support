<?php

declare(strict_types=1);

namespace Tests\Pander\DataLoaderSupport;

use Overblog\DataLoader\DataLoader;
use Pander\DataLoaderSupport\Dynamic\DataLoaderFactoryInterface;
use Pander\DataLoaderSupport\Dynamic\MemoizedDataLoaderFactory;
use PHPUnit\Framework\TestCase;

class MemoizedDataLoaderFactoryTest extends TestCase {
  private DataLoaderFactoryInterface $mockInner;
  private MemoizedDataLoaderFactory $factory;

  public function setUp(): void {
    $this->mockInner = $this->createMock(DataLoaderFactoryInterface::class);
    $this->factory = new MemoizedDataLoaderFactory($this->mockInner);
  }

  public function testMemoizesPrimaryKeyLoader(): void {
    $mockLoader = $this->createMock(DataLoader::class);

    $this->mockInner
      ->expects($this->once())
      ->method('createPrimaryKeyLoader')
      ->with('Entity', 'id')
      ->willReturn($mockLoader);

    $loader1 = $this->factory->createPrimaryKeyLoader('Entity', 'id');
    $loader2 = $this->factory->createPrimaryKeyLoader('Entity', 'id');

    $this->assertSame($loader1, $loader2);
  }

  public function testMemoizesByParentLoader(): void {
    $mockLoader = $this->createMock(DataLoader::class);

    $this->mockInner
      ->expects($this->once())
      ->method('createByParentLoader')
      ->with('Entity', 'parent', ['param1'])
      ->willReturn($mockLoader);

    $loader1 = $this->factory->createByParentLoader('Entity', 'parent', ['param1']);
    $loader2 = $this->factory->createByParentLoader('Entity', 'parent', ['param1']);

    $this->assertSame($loader1, $loader2);
  }

  public function testMemoizesByJoinTableLoader(): void {
    $mockLoader = $this->createMock(DataLoader::class);

    $this->mockInner
      ->expects($this->once())
      ->method('createByJoinTableLoader')
      ->with('Entity', 'joinTable', ['param1'])
      ->willReturn($mockLoader);

    $loader1 = $this->factory->createByJoinTableLoader('Entity', 'joinTable', ['param1']);
    $loader2 = $this->factory->createByJoinTableLoader('Entity', 'joinTable', ['param1']);

    $this->assertSame($loader1, $loader2);
  }

  public function testDifferentParametersCreateDifferentLoaders(): void {
    $mockLoader1 = $this->createMock(DataLoader::class);
    $mockLoader2 = $this->createMock(DataLoader::class);

    $this->mockInner
      ->expects($this->exactly(2))
      ->method('createPrimaryKeyLoader')
      ->willReturnOnConsecutiveCalls($mockLoader1, $mockLoader2);

    $loader1 = $this->factory->createPrimaryKeyLoader('Entity', 'id');
    $loader2 = $this->factory->createPrimaryKeyLoader('Entity', 'name');

    $this->assertNotSame($loader1, $loader2);
  }
}
