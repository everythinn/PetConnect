<?php

namespace App\Entity;

use App\Enum\ItemEffectEnum;
use App\Enum\ItemTypeEnum;
use App\Repository\ItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(enumType: ItemTypeEnum::class)]
    private ItemTypeEnum $type;

    #[ORM\Column(enumType: ItemEffectEnum::class)]
    private ItemEffectEnum $effect;

    #[ORM\Column]
    private int $effectValue = 0;

    #[ORM\Column(length: 500)]
    private string $description = '';

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): ItemTypeEnum
    {
        return $this->type;
    }

    public function setType(ItemTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getEffect(): ItemEffectEnum
    {
        return $this->effect;
    }

    public function setEffect(ItemEffectEnum $effect): static
    {
        $this->effect = $effect;

        return $this;
    }

    public function getEffectValue(): int
    {
        return $this->effectValue;
    }

    public function setEffectValue(int $effectValue): static
    {
        $this->effectValue = $effectValue;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
