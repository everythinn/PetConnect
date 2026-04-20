<?php

namespace App\Entity;

use App\Enum\DelegationStatusEnum;
use App\Repository\DelegationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DelegationRepository::class)]
class Delegation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'delegations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pet $pet = null;

    #[ORM\ManyToOne(inversedBy: 'delegationsAsOwner')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne(inversedBy: 'delegationsAsCaretaker')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $caretaker = null;

    #[ORM\Column]
    private \DateTimeImmutable $startDate;

    #[ORM\Column]
    private \DateTimeImmutable $endDate;

    #[ORM\Column(enumType: DelegationStatusEnum::class)]
    private DelegationStatusEnum $status = DelegationStatusEnum::PENDING;

    public function __construct()
    {
        $this->startDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPet(): ?Pet
    {
        return $this->pet;
    }

    public function setPet(?Pet $pet): static
    {
        $this->pet = $pet;

        return $this;
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

    public function getCaretaker(): ?User
    {
        return $this->caretaker;
    }

    public function setCaretaker(?User $caretaker): static
    {
        $this->caretaker = $caretaker;

        return $this;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getStatus(): DelegationStatusEnum
    {
        return $this->status;
    }

    public function setStatus(DelegationStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }
}
