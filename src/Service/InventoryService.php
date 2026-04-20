<?php

namespace App\Service;

use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\User;
use App\Repository\InventoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class InventoryService
{
    public function __construct(
        private readonly InventoryRepository $inventoryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Get or create inventory for user
     */
    public function getOrCreateInventory(User $user): Inventory
    {
        $inventory = $this->inventoryRepository->findByUser($user);

        if (!$inventory) {
            $inventory = new Inventory();
            $inventory->setUser($user);
            $inventory->setItems([]);
            $this->entityManager->persist($inventory);
            $this->entityManager->flush();
        }

        return $inventory;
    }

    /**
     * Add item to inventory
     */
    public function addItem(User $user, int $itemId, int $quantity = 1): void
    {
        $inventory = $this->getOrCreateInventory($user);
        $inventory->addItem($itemId, $quantity);
        $this->entityManager->flush();
    }

    /**
     * Remove item from inventory
     */
    public function removeItem(User $user, int $itemId, int $quantity = 1): bool
    {
        $inventory = $this->getOrCreateInventory($user);

        if (!$inventory->hasItem($itemId, $quantity)) {
            return false;
        }

        $inventory->removeItem($itemId, $quantity);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Check if user has item
     */
    public function hasItem(User $user, int $itemId, int $quantity = 1): bool
    {
        $inventory = $this->getOrCreateInventory($user);

        return $inventory->hasItem($itemId, $quantity);
    }

    /**
     * Get item quantity
     */
    public function getItemQuantity(User $user, int $itemId): int
    {
        $inventory = $this->getOrCreateInventory($user);

        return $inventory->getItemQuantity($itemId);
    }

    /**
     * Use an item on a pet
     */
    public function useItem(User $user, Item $item, int $quantity = 1): bool
    {
        return $this->removeItem($user, $item->getId(), $quantity);
    }
}
