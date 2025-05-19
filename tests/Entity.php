<?php

declare(strict_types=1);

namespace Tests\Pander\DataLoaderSupport;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class Entity {
  #[ORM\Id()]
  #[ORM\Column()]
  #[ORM\GeneratedValue()]
  public ?int $id = null;

  #[ORM\Column(type: 'string')]
  public string $name;

  #[ORM\ManyToOne(targetEntity: Entity::class)]
  #[ORM\JoinColumn(name: 'parent_id', nullable: true)]
  public ?Entity $parent = null;

  #[ORM\ManyToMany(targetEntity: Entity::class)]
  #[ORM\JoinTable(name: 'entity_entity')]
  #[ORM\JoinColumn(name: 'left_id')]
  #[ORM\InverseJoinColumn(name: 'right_id')]
  public Collection $joinTable;

  public function __construct(string $name, ?Entity $parent = null) {
    $this->name = $name;
    $this->parent = $parent;
    $this->joinTable = new ArrayCollection();
  }
}
