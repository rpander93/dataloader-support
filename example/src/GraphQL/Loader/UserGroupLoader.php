<?php

declare(strict_types=1);

namespace App\GraphQL\Loader;

use App\Entity\User;
use App\Entity\UserGroup;
use GraphQL\Executor\Promise\Promise;
use Pander\DataLoaderSupport\LoaderInterface;

class UserGroupLoader {
  public function __construct(private LoaderInterface $loader) {
    // ..
  }

  // Loads a list of `UserGroup` entities by their IDs.
  public function load(array $userGroupIds): Promise {
    return $this->loader->load(UserGroup::class, $userGroupIds);
  }

  // Loads a list of `UserGroup` entities per user ID.
  public function loadByUser(array $userIds): Promise {
    return $this->loader->loadByJoinTable(User::class, 'groups', $userIds);
  }
}
