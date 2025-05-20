<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Annotation as GQL;

#[ORM\Entity()]
#[ORM\Table(name: 'users')]
#[GQL\Type(name: 'User')]
class User {
  #[ORM\Id]
  #[ORM\GeneratedValue()]
  #[ORM\Column()]
  #[GQL\Field(name: 'id', type: 'ID!')]
  private int $id;

  #[ORM\Column(length: 255)]
  #[GQL\Field(name: 'name', type: 'String!')]
  private string $name;

  #[ORM\Column(length: 255, unique: true)]
  #[GQL\Field(name: 'email', type: 'String!')]
  private string $email;

  #[ORM\Column]
  #[GQL\Field(name: 'isActive', type: 'Boolean!')]
  private bool $isActive = true;

  #[ORM\ManyToMany(targetEntity: UserGroup::class)]
  #[ORM\JoinTable(name: 'user_group_members')]
  #[GQL\Field(name: 'groups', type: '[UserGroup!]!', resolve: "service('user_user_groups_loader').load(value.getId())")]
  private Collection $groups;

  public function __construct(string $name, string $email, bool $isActive = true) {
    $this->name = $name;
    $this->email = $email;
    $this->isActive = $isActive;
    $this->groups = new ArrayCollection();
  }

  public function getId(): int {
    return $this->id;
  }

  public function getName(): string {
    return $this->name;
  }

  public function getEmail(): string {
    return $this->email;
  }

  public function isActive(): bool {
    return $this->isActive;
  }

  public function setIsActive(bool $isActive): void {
    $this->isActive = $isActive;
  }

  /** @return Collection<int, UserGroup> */
  public function getGroups(): Collection {
    return $this->groups;
  }

  public function addGroup(UserGroup $group): void {
    if (!$this->groups->contains($group)) {
      $this->groups->add($group);
      $group->addMember($this);
    }
  }

  public function removeGroup(UserGroup $group): void {
    if ($this->groups->removeElement($group)) {
      $group->removeMember($this);
    }
  }
}
