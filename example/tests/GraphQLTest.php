<?php

declare(strict_types=1);

namespace Tests\App;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GraphQLTest extends WebTestCase {
  public function testQueriesUsers(): void {
    $queryString = <<<'GRAPHQL'
      query {
        users {
          id
          name
          groups {
            id
            name
          }
        }

        user(id: 1) {
          id
          name
          groups {
            id
            name
          }
        }
      }
      GRAPHQL;

    $client = $this->createClient();
    $response = $client->request('POST', '/graphql', [
      'query' => $queryString,
    ]);

    static::assertResponseIsSuccessful();
    static::assertJson($response);

    $content = json_decode($response->getContents(), associative: true);
    static::assertArrayNotHasKey('errors', $content);

    $data = $content['data'];
    static::assertArrayHasKey('users', $data);
    static::assertArrayHasKey('user', $data);
    static::assertSame(1, $data['user']['id']);

    /** @var EntityManagerInterface */
    $entityManager = static::getContainer()->get(EntityManagerInterface::class);

    foreach ($data['users'] as $user) {
      /** @var User */
      $entity = $entityManager->find(User::class, $user['id']);
      static::assertCount($entity->getGroups()->count(), $user['groups']);
    }
  }
}
