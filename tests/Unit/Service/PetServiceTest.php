<?php

namespace App\Tests\Unit\Service;

use App\Entity\Pet;
use App\Entity\User;
use App\Enum\SpeciesEnum;
use App\Repository\PetRepository;
use App\Service\CareActionService;
use App\Service\PetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PetServiceTest extends TestCase
{
    private PetService $petService;
    private PetRepository&MockObject $petRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private CareActionService&MockObject $careActionService;
    private User $testUser;

    protected function setUp(): void
    {
        $this->petRepository = $this->createMock(PetRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->careActionService = $this->createMock(CareActionService::class);

        $this->petService = new PetService(
            $this->petRepository,
            $this->entityManager,
            $this->careActionService,
        );

        $this->testUser = new User();
        $this->testUser->setUsername('testuser');
        $this->testUser->setEmail('test@example.com');
    }

    public function testAdoptPet(): void
    {
        
        $this->petRepository->expects($this->once())
            ->method('findByOwner')
            ->with($this->testUser)
            ->willReturn([]);

        
        $pet = $this->petService->adoptPet($this->testUser, 'Fluffy', SpeciesEnum::CAT);

        
        $this->assertInstanceOf(Pet::class, $pet);
        $this->assertEquals('Fluffy', $pet->getName());
        $this->assertEquals(SpeciesEnum::CAT, $pet->getSpecies());
        $this->assertEquals($this->testUser, $pet->getOwner());
        $this->assertEquals(1, $pet->getLevel());
        $this->assertEquals(0, $pet->getXp());
        $this->assertEquals(100, $pet->getHunger());
        $this->assertEquals(100, $pet->getHappiness());
        $this->assertEquals(100, $pet->getHealth());
        $this->assertEquals(100, $pet->getEnergy());
        $this->assertTrue($pet->isAlive());
    }

    public function testAdoptPetMaxReached(): void
    {
        
        $pets = array_fill(0, PetService::MAX_PETS_PER_USER, new Pet());
        $this->petRepository->expects($this->once())
            ->method('findByOwner')
            ->with($this->testUser)
            ->willReturn($pets);

        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Maximum number of pets reached');

        
        $this->petService->adoptPet($this->testUser, 'Fluffy', SpeciesEnum::CAT);
    }

    public function testFeedPet(): void
    {
        
        $pet = $this->createTestPet(50, 100);
        $initialXp = $pet->getXp();
        $initialHunger = $pet->getHunger();

        
        $this->petService->feedPet($pet, $this->testUser, 20);

        
        $this->assertEquals(min(100, $initialHunger + 20), $pet->getHunger());
        $this->assertEquals($initialXp + 10, $pet->getXp());
    }

    public function testFeedDeadPet(): void
    {
        
        $pet = $this->createTestPet();
        $pet->setIsAlive(false);

        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot feed a dead pet');

        
        $this->petService->feedPet($pet, $this->testUser);
    }

    public function testPlayWithPet(): void
    {
        
        $pet = $this->createTestPet(50, 100);
        $initialHappiness = $pet->getHappiness();
        $initialEnergy = $pet->getEnergy();
        $initialHunger = $pet->getHunger();

        
        $this->petService->playWithPet($pet, $this->testUser, 15);

        
        $this->assertEquals($initialHappiness + 15, $pet->getHappiness());
        $this->assertEquals($initialEnergy - 15, $pet->getEnergy());
        $this->assertEquals($initialHunger + 5, $pet->getHunger());
    }

    public function testPlayWithTiredPet(): void
    {
        
        $pet = $this->createTestPet(10, 100); 
        $pet->setEnergy(10);

        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pet is too tired to play');

        
        $this->petService->playWithPet($pet, $this->testUser);
    }

    public function testHealPet(): void
    {
        
        $pet = $this->createTestPet(50, 60); 
        $pet->setHealth(60);
        $initialHealth = $pet->getHealth();
        $initialXp = $pet->getXp();

        
        $this->petService->healPet($pet, $this->testUser, 30);

        
        $this->assertEquals($initialHealth + 30, $pet->getHealth());
        $this->assertEquals($initialXp + 10, $pet->getXp());
    }

    public function testSleepPet(): void
    {
        
        $pet = $this->createTestPet(50, 40); 
        $pet->setEnergy(40);
        $initialEnergy = $pet->getEnergy();
        $initialXp = $pet->getXp();

        
        $this->petService->sleepPet($pet, $this->testUser, 50);

        
        $this->assertEquals($initialEnergy + 50, $pet->getEnergy());
        $this->assertEquals($initialXp + 10, $pet->getXp());
    }

    public function testBathePet(): void
    {
        
        $pet = $this->createTestPet(50, 70); 
        $pet->setHealth(70);
        $initialHealth = $pet->getHealth();
        $initialHappiness = $pet->getHappiness();
        $initialEnergy = $pet->getEnergy();

        
        $this->petService->bathePet($pet, $this->testUser, 25);

        
        $this->assertEquals($initialHealth + 25, $pet->getHealth());
        $this->assertEquals($initialHappiness + 10, $pet->getHappiness());
        $this->assertEquals($initialEnergy - 10, $pet->getEnergy());
    }

    public function testCheckLevelUp(): void
    {
        
        $pet = $this->createTestPet();
        $pet->setLevel(1);
        $pet->setXp(0);
        $pet->setXpToNextLevel(100);

        
        for ($i = 0; $i < 10; $i++) {
            $this->petService->feedPet($pet, $this->testUser);
        }

        
        $this->assertGreaterThan(1, $pet->getLevel());
    }

    public function testDeletePet(): void
    {
        
        $pet = $this->createTestPet();
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($pet);
        $this->entityManager->expects($this->once())
            ->method('flush');

        
        $this->petService->deletePet($pet);

        
    }

    
    private function createTestPet(int $hunger = 50, int $health = 100): Pet
    {
        $pet = new Pet();
        $pet->setOwner($this->testUser);
        $pet->setName('TestPet');
        $pet->setSpecies(SpeciesEnum::DOG);
        $pet->setLevel(1);
        $pet->setXp(0);
        $pet->setXpToNextLevel(100);
        $pet->setHunger($hunger);
        $pet->setHappiness(50);
        $pet->setHealth($health);
        $pet->setEnergy(100);
        $pet->setIsAlive(true);

        return $pet;
    }
}
