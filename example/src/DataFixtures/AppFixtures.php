<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture {
  public function load(ObjectManager $manager): void {
    $groups = $this->createUserGroups($manager);
    $this->createUsersInGroups($manager, $groups);
  }

  /** @return UserGroup[] */
  private function createUserGroups(ObjectManager $manager): array {
    $groups = [
      new UserGroup('Alpha', 'System administrators with full access'),
      new UserGroup('Beta', 'Content moderators with limited administrative rights'),
      new UserGroup('Charlie', 'Regular authenticated users'),
      new UserGroup('Delta', 'Temporary visitors with minimal access'),
    ];

    foreach ($groups as $group) {
      $manager->persist($group);
    }

    $manager->flush();

    return $groups;
  }

  private function createUsersInGroups(ObjectManager $manager, array $groups): void {
    $users = [
      new User('Alice', 'alice@example.com'),
      new User('Bob', 'bob@example.com'),
      new User('Charlie', 'charlie@example.com'),
      new User('Dave', 'dave@example.com'),
      new User('Eve', 'eve@example.com'),
      new User('Frank', 'frank@example.com'),
      new User('Grace', 'grace@example.com'),
      new User('Heidi', 'heid@example.com'),
      new User('Ivan', 'ivan@example.com'),
      new User('Judy', 'judy@example.com'),
    ];

    foreach ($users as $user) {
      $user->setIsActive((bool) random_int(0, 1));

      foreach (array_rand($groups, 2) as $groupId) {
        $user->addGroup($groups[$groupId]);
      }

      $manager->persist($user);
    }

    $manager->flush();
  }
}
