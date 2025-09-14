<?php

declare(strict_types=1);

namespace App\GraphQL\Resolver;

use App\Entity\User;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\QueryInterface;
use Pander\DataLoaderSupport\Dynamic\MemoizedDataLoaderFactory;

class UserUserGroupResolver implements QueryInterface {
  public function __construct(private MemoizedDataLoaderFactory $dataLoaderFactory) {
  }

  public function __invoke(User $user, Argument $args) {
    $params = $args->getArrayCopy();
    $loader = $this->dataLoaderFactory->createByJoinTableLoader(User::class, 'groups', $params);

    return $loader->load($user->getId());
  }
}
