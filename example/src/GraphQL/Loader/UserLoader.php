<?php

declare(strict_types=1);

namespace App\GraphQL\Loader;

use App\Entity\User;
use App\Entity\UserGroup;
use GraphQL\Executor\Promise\Promise;
use Pander\DataLoaderSupport\LoaderInterface;

class UserLoader {
  public function __construct(private LoaderInterface $loader) {
    // ..
  }

  // Loads a list of `User` entities by their IDs.
  public function load(array $userIds): Promise {
    return $this->loader->load(User::class, $userIds);
  }

  // Loads a list of `User` entities per group ID.
  public function loadByGroup(array $userGroupIds): Promise {
    return $this->loader->loadByJoinTable(UserGroup::class, 'users', $userGroupIds);
  }
}
