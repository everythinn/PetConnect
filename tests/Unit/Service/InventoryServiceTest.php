<?php

namespace App\Tests\Unit\Service;

use App\Entity\Inventory;
use App\Entity\User;
use App\Repository\InventoryRepository;
use App\Service\InventoryService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class InventoryServiceTest extends TestCase
{
    private InventoryService $inventoryService;
    private InventoryRepository&MockObject $inventoryRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private User $testUser;

    protected function setUp(): void
    {
        $this->inventoryRepository = $this->createMock(InventoryRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->inventoryService = new InventoryService(
            $this->inventoryRepository,
            $this->entityManager,
        );

        $this->testUser = new User();
        $this->testUser->setUsername('testuser');
        $this->testUser->setEmail('test@example.com');
    }

    public function testGetOrCreateInventoryExists(): void
    {
        
        $inventory = new Inventory();
        $inventory->setUser($this->testUser);
        $inventory->setItems([]);

        $this->inventoryRepository->expects($this->once())
            ->method('findByUser')
            ->with($this->testUser)
            ->willReturn($inventory);

        
        $result = $this->inventoryService->getOrCreateInventory($this->testUser);

        
        $this->assertSame($inventory, $result);
    }

    public function testGetOrCreateInventoryCreatesNew(): void
    {
        
        $this->inventoryRepository->expects($this->once())
            ->method('findByUser')
            ->with($this->testUser)
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist');
        $this->entityManager->expects($this->once())
            ->method('flush');

        
        $result = $this->inventoryService->getOrCreateInventory($this->testUser);

        
        $this->assertInstanceOf(Inventory::class, $result);
        $this->assertEquals($this->testUser, $result->getUser());
    }

    public function testAddItem(): void
    {
        
        $inventory = new Inventory();
        $inventory->setUser($this->testUser);
        $inventory->setItems([]);

        $this->inventoryRepository->expects($this->once())
            ->method('findByUser')
            ->with($this->testUser)
            ->willReturn($inventory);

        $this->entityManager->expects($this->once())
            ->method('flush');

        
        $this->inventoryService->addItem($this->testUser, 1, 5);

        
        $this->assertEquals(5, $inventory->getItemQuantity(1));
    }

    public function testRemoveItem(): void
    {
        
        $inventory = new Inventory();
        $inventory->setUser($this->testUser);
        $inventory->setItems([1 => 10]);

        $this->inventoryRepository->expects($this->once())
            ->method('findByUser')
            ->with($this->testUser)
            ->willReturn($inventory);

        $this->entityManager->expects($this->once())
            ->method('flush');

        
        $result = $this->inventoryService->removeItem($this->testUser, 1, 3);

        
        $this->assertTrue($result);
        $this->assertEquals(7, $inventory->getItemQuantity(1));
    }

    public function testRemoveItemNotEnough(): void
    {
        
        $inventory = new Inventory();
        $inventory->setUser($this->testUser);
        $inventory->setItems([1 => 2]);

        $this->inventoryRepository->expects($this->once())
            ->method('findByUser')
            ->with($this->testUser)
            ->willReturn($inventory);

        
        $result = $this->inventoryService->removeItem($this->testUser, 1, 5);

        
        $this->assertFalse($result);
        $this->assertEquals(2, $inventory->getItemQuantity(1));
    }

    public function testHasItem(): void
    {
        
        $inventory = new Inventory();
        $inventory->setUser($this->testUser);
        $inventory->setItems([1 => 5]);

        $this->inventoryRepository->expects($this->once())
            ->method('findByUser')
            ->with($this->testUser)
            ->willReturn($inventory);

        
        $result = $this->inventoryService->hasItem($this->testUser, 1, 3);

        
        $this->assertTrue($result);
    }

    public function testHasItemNotEnough(): void
    {
        
        $inventory = new Inventory();
        $inventory->setUser($this->testUser);
        $inventory->setItems([1 => 2]);

        $this->inventoryRepository->expects($this->once())
            ->method('findByUser')
            ->with($this->testUser)
            ->willReturn($inventory);

        
        $result = $this->inventoryService->hasItem($this->testUser, 1, 5);

        
        $this->assertFalse($result);
    }
}
