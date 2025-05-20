<?php

declare(strict_types=1);

namespace App\GraphQL\Resolver;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\Provider()]
class UserResolver {
  public function __construct(private EntityManagerInterface $entityManager) {
    // ..
  }

  #[GQL\Query(name: 'users', type: '[User!]!')]
  #[GQL\Arg(name: 'isActive', type: 'Boolean')]
  #[GQL\Arg(name: 'name', type: 'String')]
  #[GQL\Arg(name: 'limit', type: 'Int')]
  public function users(?bool $isActive = null, ?string $name = null, ?int $limit = 10): array {
    $builder = $this->entityManager
      ->createQueryBuilder();
    $builder
      ->select('u')
      ->from(User::class, 'u');

    if (null !== $isActive) {
      $builder
        ->andWhere('u.isActive = :isActive')
        ->setParameter('isActive', $isActive);
    }

    if (null !== $name) {
      $builder
        ->andWhere('u.name LIKE :name')
        ->setParameter('name', '%'.$name.'%');
    }

    if (null !== $limit) {
      $builder
        ->setMaxResults($limit);
    }

    return $builder
      ->getQuery()
      ->execute();
  }

  #[GQL\Query(name: 'user', type: 'User')]
  #[GQL\Arg(name: 'id', type: 'ID!')]
  public function user(int $id): ?User {
    return $this->entityManager->find(User::class, $id);
  }
}
