<?php

declare(strict_types=1);

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Pander\DataLoaderSupport\Doctrine\IdxFunction;
use Tests\Pander\DataLoaderSupport\Entity;

function addFixturesInDatabase(EntityManager $entityManager) {
  // reset database
  $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
  $schemaTool = new SchemaTool($entityManager);
  $schemaTool->dropSchema($metadata);
  $schemaTool->createSchema($metadata);

  // create `idx` function
  $entityManager->getConnection()->executeQuery('CREATE EXTENSION IF NOT EXISTS intarray;');

  $entities = [];
  for ($i = 0; $i < 20; ++$i) {
    $parent = $i > 0 ? $entities[random_int(0, $i - 1)] : null;
    $collection = new ArrayCollection($i > 0 ? array_random_values($entities, random_int(1, \count($entities))) : []);

    $entity = new Entity('Entity#'.$i, $parent, $collection);
    $entities[] = $entity;

    $entityManager->persist($entity);
  }

  $entityManager->flush();
}

function array_random_values(array $input, int $count): array {
  $keys = array_rand($input, $count);

  return \is_array($keys)
      ? array_map(array: $keys, callback: fn ($key) => $input[$key])
      : [$input[$keys]];
}

return function () {
  $connectionParams = (new DsnParser())->parse(getenv('DATABASE_URL'));
  $connection = DriverManager::getConnection($connectionParams);
  $configuration = ORMSetup::createAttributeMetadataConfiguration([__DIR__], isDevMode: true);
  $configuration->setCustomStringFunctions(['IDX' => IdxFunction::class]);
  $entityManager = new EntityManager($connection, $configuration);
  addFixturesInDatabase($entityManager);

  return $entityManager;
};
