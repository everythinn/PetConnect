<?php

namespace App\Entity;

use App\Repository\InventoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryRepository::class)]
class Inventory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'inventory')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private array $items = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Add an item to inventory
     * Format: ['itemId' => quantity, ...]
     */
    public function addItem(int $itemId, int $quantity = 1): static
    {
        $this->items[$itemId] = ($this->items[$itemId] ?? 0) + $quantity;

        return $this;
    }

    /**
     * Remove an item from inventory
     */
    public function removeItem(int $itemId, int $quantity = 1): static
    {
        if (isset($this->items[$itemId])) {
            $this->items[$itemId] -= $quantity;
            if ($this->items[$itemId] <= 0) {
                unset($this->items[$itemId]);
            }
        }

        return $this;
    }

    /**
     * Get item quantity
     */
    public function getItemQuantity(int $itemId): int
    {
        return $this->items[$itemId] ?? 0;
    }

    /**
     * Check if user has enough items
     */
    public function hasItem(int $itemId, int $quantity = 1): bool
    {
        return ($this->items[$itemId] ?? 0) >= $quantity;
    }
}
