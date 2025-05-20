# Pander\DataLoaderSupport

This package complements github.com/overblog/dataloader-php (and github.com/overblog/dataloader-bundle) by making it straightforward to write data loaders - typically for use with a GraphQL api - that load entities via Doctrine ORM.

## The need

As explained [here](https://github.com/overblog/dataloader-php?tab=readme-ov-file#batch-function), in overblog/dataloader-php, the resolver function `$batchLoadFn` is required to return elements in the same order as the `$keys` provided. Any values not found in the source must be substituded for (with `NULL`).

The example given [in the documentation of dataloader-bundle](https://github.com/overblog/dataloader-bundle?tab=readme-ov-file#combine-with-graphqlbundle) is thus incorrect, because a SQL `WHERE x.id IN (?, ?, ...)` does not make any guarantee that the input order will be respected. Moreover, if 1 or more id's are not available in the database, they obviously will not be returned.

This package takes care of both these issues for you.

## Basic example

A typical example would have a data loader that loads `User` entities from the database.

The configuration with dataloader-bundle would look like:

````yaml
overblog_dataloader:
  loaders:
    users:
      batch_load_fn: '@App\Loader\UserLoader:load'
````

with a schema like:

````gql
query {
  users {
    id
    username
  }
}
````

and the implementation of the `UserLoader` would look like:

````php
<?php

use App\Entity\User;
use Pander\DataLoaderSupport\LoaderInterface;

class UserLoader {
  public function __construct(LoaderInterface $loader) {
    // ..
  }

  public function load(array $userIds): Promise {
    return $this->loader->load(User::class, $userIds);
  }
}
````

where the `LoaderInterface` implementation takes care of loading all `$userIds` in the correct order and making sure that any missing values are replaced with `NULL`s.

## Additional load methods

In addition to loading entities directly by entity class and ids (using the `load` method), this package supports 2 additional methods of loading entities.

### Loading the *Many side of a OneToMany

If your GraphQL schema contains parent-child relations, you can deduplicate & batch these relations using a data loader. Imagine the following schema:

````gql
query {
  adult {
    id
    name
    children {
      id
      name
    }
  }
}
````

backed by the following Doctrine entity:

````php
<?php

use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

class Adult {
  #[ORM\Id()]
  #[ORM\Column()]
  public ?int $id = null;

  #[ORM\Column()]
  public string $name;

  #[ORM\OneToMany(targetEntity: Child::class)]
  public Collection $children;
}

class Child {
  #[ORM\Id()]
  #[ORM\Column()]
  public ?int $id = null;

  #[ORM\Column()]
  public string $name;

  #[ORM\ManyToOne()]
  public ?Adult $adult = null;
}

````

which can easily be loaded with the following loader:

````php
<?php

use App\Entity\Child;
use Pander\DataLoaderSupport\LoaderInterface;

class AdultChildrenLoader {
  public function __construct(LoaderInterface $loader) {
    // ..
  }

  public function load(array $adultIds): Promise {
    return $this->loader->loadByParent(Child::class, 'adult', $adultIds);
  }
}
````

### Loading *Many via a ManyToMany (Join table)

It could also be that the Doctrine relation is defined via a join table. Consider the previous entities but now with a join table from Adult to Child:

````php
<?php

use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

class Adult {
  #[ORM\Id()]
  #[ORM\Column()]
  public ?int $id = null;

  #[ORM\Column()]
  public string $name;

  #[ORM\ManyToMany(targetEntity: Child::class)]
  #[ORM\JoinTable()]
  public Collection $children;
}

class Child {
  #[ORM\Id()]
  #[ORM\Column()]
  public ?int $id = null;

  #[ORM\Column()]
  public string $name;
}

````

the loader will look like:

````php
<?php

use App\Entity\Adult;
use Pander\DataLoaderSupport\LoaderInterface;

class AdultChildrenLoader {
  public function __construct(LoaderInterface $loader) {
    // ..
  }

  public function load(array $adultIds): Promise {
    return $this->loader->loadByJoinTable(Adult::class, 'children', $adultIds);
  }
}
````