<?php

namespace App\Entity;

use App\Enum\ActionTypeEnum;
use App\Repository\CareActionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CareActionRepository::class)]
class CareAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'careActions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pet $pet = null;

    #[ORM\ManyToOne(inversedBy: 'careActionsPerformed')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $performer = null;

    #[ORM\Column(enumType: ActionTypeEnum::class)]
    private ActionTypeEnum $actionType;

    #[ORM\Column]
    private int $statDelta = 0;

    #[ORM\Column]
    private int $xpEarned = 0;

    #[ORM\Column]
    private \DateTimeImmutable $performedAt;

    public function __construct()
    {
        $this->performedAt = new \DateTimeImmutable();
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

    public function getPerformer(): ?User
    {
        return $this->performer;
    }

    public function setPerformer(?User $performer): static
    {
        $this->performer = $performer;

        return $this;
    }

    public function getActionType(): ActionTypeEnum
    {
        return $this->actionType;
    }

    public function setActionType(ActionTypeEnum $actionType): static
    {
        $this->actionType = $actionType;

        return $this;
    }

    public function getStatDelta(): int
    {
        return $this->statDelta;
    }

    public function setStatDelta(int $statDelta): static
    {
        $this->statDelta = $statDelta;

        return $this;
    }

    public function getXpEarned(): int
    {
        return $this->xpEarned;
    }

    public function setXpEarned(int $xpEarned): static
    {
        $this->xpEarned = $xpEarned;

        return $this;
    }

    public function getPerformedAt(): \DateTimeImmutable
    {
        return $this->performedAt;
    }

    public function setPerformedAt(\DateTimeImmutable $performedAt): static
    {
        $this->performedAt = $performedAt;

        return $this;
    }
}
