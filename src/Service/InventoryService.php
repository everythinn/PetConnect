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

    // get or create inventory
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


    public function addItem(User $user, int $itemId, int $quantity = 1): void
    {
        $inventory = $this->getOrCreateInventory($user);
        $inventory->addItem($itemId, $quantity);
        $this->entityManager->flush();
    }


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


    public function hasItem(User $user, int $itemId, int $quantity = 1): bool
    {
        $inventory = $this->getOrCreateInventory($user);

        return $inventory->hasItem($itemId, $quantity);
    }


    public function getItemQuantity(User $user, int $itemId): int
    {
        $inventory = $this->getOrCreateInventory($user);

        return $inventory->getItemQuantity($itemId);
    }


    public function useItem(User $user, Item $item, int $quantity = 1): bool
    {
        return $this->removeItem($user, $item->getId(), $quantity);
    }
}
