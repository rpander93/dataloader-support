<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Annotation as GQL;

#[ORM\Entity()]
#[ORM\Table(name: 'user_groups')]
#[GQL\Type(name: 'UserGroup')]
class UserGroup {
  #[ORM\Id]
  #[ORM\GeneratedValue()]
  #[ORM\Column(length: 36)]
  #[GQL\Field(name: 'id', type: 'ID!')]
  private int $id;

  #[ORM\Column()]
  #[GQL\Field(name: 'name', type: 'String!')]
  private string $name;

  #[ORM\Column(nullable: true)]
  #[GQL\Field(name: 'description', type: 'String')]
  private ?string $description = null;

  #[ORM\Column()]
  #[GQL\Field(name: 'isActive', type: 'Boolean!')]
  private bool $isActive = true;

  #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'groups')]
  #[GQL\Field(name: 'members', type: '[User!]!', resolve: "service('user_group_users_loader').load(value.getId())")]
  private Collection $members;

  public function __construct(string $name, ?string $description = null, bool $isActive = true) {
    $this->name = $name;
    $this->description = $description;
    $this->members = new ArrayCollection();
    $this->isActive = $isActive;
  }

  public function getId(): int {
    return $this->id;
  }

  public function getName(): string {
    return $this->name;
  }

  public function setName(string $name): void {
    $this->name = $name;
  }

  public function getDescription(): ?string {
    return $this->description;
  }

  public function setDescription(?string $description): void {
    $this->description = $description;
  }

  public function getIsActive(): bool {
    return $this->isActive;
  }

  public function setIsActive(bool $isActive): void {
    $this->isActive = $isActive;
  }

  /** @return Collection<int, User> */
  public function getMembers(): Collection {
    return $this->members;
  }

  public function addMember(User $user): void {
    if (!$this->members->contains($user)) {
      $this->members->add($user);
    }
  }

  public function removeMember(User $user): void {
    $this->members->removeElement($user);
  }
}
