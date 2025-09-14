<?php

declare(strict_types=1);

namespace Tests\App;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GraphQLTest extends WebTestCase {
  public static function getKernelClass(): string {
    return Kernel::class;
  }

  public function testQueriesUsers(): void {
    $queryString = <<<'GRAPHQL'
      query ($userId: ID!, $isActive: Boolean) {
        users {
          id
          name
          groups(isActive: $isActive) {
            id
            name
          }
        }

        user(id: $userId) {
          id
          name
          groups {
            id
            name
            members {
              id
              name
            }
          }
        }
      }
      GRAPHQL;

    $client = $this->createClient();

    /** @var EntityManagerInterface */
    $entityManager = static::getContainer()->get(EntityManagerInterface::class);
    /** @var User */
    $user = $entityManager->createQueryBuilder()
      ->select('u')
      ->from(User::class, 'u')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();

    $client->request('POST', '/graphql/', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
      'query' => $queryString,
      'variables' => [
        'userId' => $user->getId(),
        'isActive' => true,
      ],
    ]));

    static::assertResponseIsSuccessful();
    $content = $client->getResponse()->getContent();
    static::assertJson($content);
    $content = json_decode($content, true);
    static::assertArrayNotHasKey('errors', $content);

    $data = $content['data'];
    static::assertArrayHasKey('users', $data);
    static::assertArrayHasKey('user', $data);
    static::assertSame($user->getId(), (int) $data['user']['id']);

    foreach ($data['users'] as $user) {
      /** @var User */
      $entity = $entityManager->find(User::class, $user['id']);
      $groups = $entity->getGroups()->filter(fn (UserGroup $g) => $g->getIsActive());
      static::assertCount($groups->count(), $user['groups']);
    }

    foreach ($data['user']['groups'] as $group) {
      /** @var UserGroup */
      $entity = $entityManager->find(UserGroup::class, $group['id']);
      static::assertCount($entity->getMembers()->count(), $group['members']);
    }
  }
}
