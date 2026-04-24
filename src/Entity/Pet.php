<?php

namespace App\Entity;

use App\Enum\SpeciesEnum;
use App\Repository\PetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PetRepository::class)]
class Pet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'pets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(enumType: SpeciesEnum::class)]
    private SpeciesEnum $species;

    #[ORM\Column]
    private int $level = 1;

    #[ORM\Column]
    private int $xp = 0;

    #[ORM\Column]
    private int $xpToNextLevel = 100;

    #[ORM\Column]
    private int $hunger = 50;

    #[ORM\Column]
    private int $happiness = 50;

    #[ORM\Column]
    private int $health = 100;

    #[ORM\Column]
    private int $energy = 100;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastInteractedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $bornAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastStatsDegradationAt = null;

    #[ORM\Column]
    private bool $isAlive = true;

    #[ORM\OneToMany(targetEntity: CareAction::class, mappedBy: 'pet', orphanRemoval: true)]
    private Collection $careActions;

    #[ORM\OneToMany(targetEntity: Delegation::class, mappedBy: 'pet', orphanRemoval: true)]
    private Collection $delegations;

    public function __construct()
    {
        $this->bornAt = new \DateTimeImmutable();
        $this->careActions = new ArrayCollection();
        $this->delegations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSpecies(): SpeciesEnum
    {
        return $this->species;
    }

    public function setSpecies(SpeciesEnum $species): static
    {
        $this->species = $species;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getXp(): int
    {
        return $this->xp;
    }

    public function setXp(int $xp): static
    {
        $this->xp = $xp;

        return $this;
    }

    public function getXpToNextLevel(): int
    {
        return $this->xpToNextLevel;
    }

    public function setXpToNextLevel(int $xpToNextLevel): static
    {
        $this->xpToNextLevel = $xpToNextLevel;

        return $this;
    }

    public function getHunger(): int
    {
        return $this->hunger;
    }

    public function setHunger(int $hunger): static
    {
        $this->hunger = max(0, min(100, $hunger));

        return $this;
    }

    public function getHappiness(): int
    {
        return $this->happiness;
    }

    public function setHappiness(int $happiness): static
    {
        $this->happiness = max(0, min(100, $happiness));

        return $this;
    }

    public function getHealth(): int
    {
        return $this->health;
    }

    public function setHealth(int $health): static
    {
        $this->health = max(0, min(100, $health));
        
        if ($this->health === 0) {
            $this->isAlive = false;
        }

        return $this;
    }

    public function getEnergy(): int
    {
        return $this->energy;
    }

    public function setEnergy(int $energy): static
    {
        $this->energy = max(0, min(100, $energy));

        return $this;
    }

    public function getLastInteractedAt(): ?\DateTimeImmutable
    {
        return $this->lastInteractedAt;
    }

    public function setLastInteractedAt(?\DateTimeImmutable $lastInteractedAt): static
    {
        $this->lastInteractedAt = $lastInteractedAt;

        return $this;
    }

    public function getBornAt(): \DateTimeImmutable
    {
        return $this->bornAt;
    }

    public function getLastStatsDegradationAt(): ?\DateTimeImmutable
    {
        return $this->lastStatsDegradationAt;
    }

    public function setLastStatsDegradationAt(?\DateTimeImmutable $lastStatsDegradationAt): static
    {
        $this->lastStatsDegradationAt = $lastStatsDegradationAt;

        return $this;
    }

    public function isAlive(): bool
    {
        return $this->isAlive;
    }

    public function setIsAlive(bool $isAlive): static
    {
        $this->isAlive = $isAlive;

        return $this;
    }

    /**
     * @return Collection<int, CareAction>
     */
    public function getCareActions(): Collection
    {
        return $this->careActions;
    }

    public function addCareAction(CareAction $careAction): static
    {
        if (!$this->careActions->contains($careAction)) {
            $this->careActions->add($careAction);
            $careAction->setPet($this);
        }

        return $this;
    }

    public function removeCareAction(CareAction $careAction): static
    {
        if ($this->careActions->removeElement($careAction)) {
            if ($careAction->getPet() === $this) {
                $careAction->setPet(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Delegation>
     */
    public function getDelegations(): Collection
    {
        return $this->delegations;
    }

    public function addDelegation(Delegation $delegation): static
    {
        if (!$this->delegations->contains($delegation)) {
            $this->delegations->add($delegation);
            $delegation->setPet($this);
        }

        return $this;
    }

    public function removeDelegation(Delegation $delegation): static
    {
        if ($this->delegations->removeElement($delegation)) {
            if ($delegation->getPet() === $this) {
                $delegation->setPet(null);
            }
        }

        return $this;
    }
}
