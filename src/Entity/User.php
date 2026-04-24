<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $email = '';

    #[ORM\Column(length: 255, unique: true)]
    private string $username = '';

    #[ORM\Column(length: 255)]
    private string $password = '';

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: Pet::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $pets;

    #[ORM\OneToMany(targetEntity: CareAction::class, mappedBy: 'performer')]
    private Collection $careActionsPerformed;

    #[ORM\OneToMany(targetEntity: Delegation::class, mappedBy: 'owner')]
    private Collection $delegationsAsOwner;

    #[ORM\OneToMany(targetEntity: Delegation::class, mappedBy: 'caretaker')]
    private Collection $delegationsAsCaretaker;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Inventory $inventory = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->pets = new ArrayCollection();
        $this->careActionsPerformed = new ArrayCollection();
        $this->delegationsAsOwner = new ArrayCollection();
        $this->delegationsAsCaretaker = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPets(): Collection
    {
        return $this->pets;
    }

    public function addPet(Pet $pet): static
    {
        if (!$this->pets->contains($pet)) {
            $this->pets->add($pet);
            $pet->setOwner($this);
        }

        return $this;
    }

    public function removePet(Pet $pet): static
    {
        if ($this->pets->removeElement($pet)) {
            if ($pet->getOwner() === $this) {
                $pet->setOwner(null);
            }
        }

        return $this;
    }

    public function getCareActionsPerformed(): Collection
    {
        return $this->careActionsPerformed;
    }

    public function addCareActionPerformed(CareAction $careAction): static
    {
        if (!$this->careActionsPerformed->contains($careAction)) {
            $this->careActionsPerformed->add($careAction);
            $careAction->setPerformer($this);
        }

        return $this;
    }

    public function removeCareActionPerformed(CareAction $careAction): static
    {
        if ($this->careActionsPerformed->removeElement($careAction)) {
            if ($careAction->getPerformer() === $this) {
                $careAction->setPerformer(null);
            }
        }

        return $this;
    }

    public function getDelegationsAsOwner(): Collection
    {
        return $this->delegationsAsOwner;
    }

    public function addDelegationAsOwner(Delegation $delegation): static
    {
        if (!$this->delegationsAsOwner->contains($delegation)) {
            $this->delegationsAsOwner->add($delegation);
            $delegation->setOwner($this);
        }

        return $this;
    }

    public function removeDelegationAsOwner(Delegation $delegation): static
    {
        if ($this->delegationsAsOwner->removeElement($delegation)) {
            if ($delegation->getOwner() === $this) {
                $delegation->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Delegation>
     */
    public function getDelegationsAsCaretaker(): Collection
    {
        return $this->delegationsAsCaretaker;
    }

    public function addDelegationAsCaretaker(Delegation $delegation): static
    {
        if (!$this->delegationsAsCaretaker->contains($delegation)) {
            $this->delegationsAsCaretaker->add($delegation);
            $delegation->setCaretaker($this);
        }

        return $this;
    }

    public function removeDelegationAsCaretaker(Delegation $delegation): static
    {
        if ($this->delegationsAsCaretaker->removeElement($delegation)) {
            if ($delegation->getCaretaker() === $this) {
                $delegation->setCaretaker(null);
            }
        }

        return $this;
    }

    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }

    public function setInventory(?Inventory $inventory): static
    {
        if ($inventory === null && $this->inventory !== null) {
            $this->inventory->setUser(null);
        }

        if ($inventory !== null && $inventory->getUser() !== $this) {
            $inventory->setUser($this);
        }

        $this->inventory = $inventory;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
