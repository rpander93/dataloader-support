<?php

declare(strict_types=1);

namespace App\GraphQL\Resolver;

use App\Entity\UserGroup;
use Doctrine\ORM\EntityManagerInterface;
use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\Provider()]
class UserGroupResolver {
  public function __construct(private EntityManagerInterface $entityManager) {
    // ..
  }

  #[GQL\Query(name: 'userGroups', type: '[UserGroup!]!')]
  #[GQL\Arg(name: 'name', type: 'String')]
  public function userGroups(?string $name = null) {
    $builder = $this->entityManager
      ->createQueryBuilder();
    $builder
      ->select('ug')
      ->from(UserGroup::class, 'ug');

    if (null !== $name) {
      $builder
        ->andWhere('ug.name LIKE :name')
        ->setParameter('name', '%'.$name.'%');
    }

    return $builder
      ->getQuery()
      ->execute();
  }

  #[GQL\Query(name: 'userGroup', type: 'UserGroup')]
  #[GQL\Arg(name: 'id', type: 'Int!')]
  public function userGroup(int $id) {
    return $this->entityManager->find(UserGroup::class, $id);
  }
}
