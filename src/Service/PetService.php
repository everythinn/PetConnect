<?php

namespace App\Service;

use App\Entity\CareAction;
use App\Entity\Pet;
use App\Entity\User;
use App\Entity\Item;
use App\Enum\ActionTypeEnum;
use App\Enum\ItemTypeEnum;
use App\Enum\SpeciesEnum;
use App\Repository\PetRepository;
use Doctrine\ORM\EntityManagerInterface;

class PetService
{
    public const MAX_PETS_PER_USER = 10;

    public function __construct(
        private readonly PetRepository $petRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CareActionService $careActionService,
    ) {
    }

    // calculate xp needed to reach next level
    private function calculateXpToNextLevel(int $level): int
    {
        return $level * 100;
    }

    // alculate remaining xp needed to reach next level
    private function calculateRemainingXp(int $level, int $currentXp): int
    {
        return $this->calculateXpToNextLevel($level) - $currentXp;
    }

    // adopt new pet
    public function adoptPet(User $owner, string $name, SpeciesEnum $species): Pet
    {
        $petCount = count($this->petRepository->findByOwner($owner));
        if ($petCount >= self::MAX_PETS_PER_USER) {
            throw new \Exception('Maximum number of pets reached');
        }

        $pet = new Pet();
        $pet->setOwner($owner);
        $pet->setName($name);
        $pet->setSpecies($species);
        $pet->setLevel(1);
        $pet->setXp(0);
        $pet->setXpToNextLevel($this->calculateRemainingXp(1, 0));
        $pet->setHunger(100);
        $pet->setHappiness(100);
        $pet->setHealth(100);
        $pet->setEnergy(100);
        $pet->setIsAlive(true);

        $this->entityManager->persist($pet);
        $this->entityManager->flush();

        return $pet;
    }

    // feed pet
    public function feedPet(Pet $pet, User $performer, int $effectValue = 20): CareAction
    {
        if (!$pet->isAlive()) {
            throw new \Exception('Cannot feed a dead pet');
        }

        $pet->setHunger(min(100, $pet->getHunger() + $effectValue));
        $pet->setLastInteractedAt(new \DateTimeImmutable());

        $xpEarned = 10;
        $pet->setXp($pet->getXp() + $xpEarned);
        $pet->setXpToNextLevel($pet->getXpToNextLevel() - $xpEarned);

        $this->checkLevelUp($pet);
        $this->checkDeath($pet);

        $careAction = $this->careActionService->logAction($pet, $performer, ActionTypeEnum::FEED, $effectValue, $xpEarned);

        $this->entityManager->flush();

        return $careAction;
    }

    // Play with pet
    public function playWithPet(Pet $pet, User $performer, int $effectValue = 15): CareAction
    {
        if (!$pet->isAlive()) {
            throw new \Exception('Cannot play with a dead pet');
        }

        if ($pet->getEnergy() < 20) {
            throw new \Exception('Pet is too tired to play');
        }

        $pet->setHappiness($pet->getHappiness() + $effectValue);
        $pet->setEnergy($pet->getEnergy() - 15);
        $pet->setHunger($pet->getHunger() + 5);
        $pet->setLastInteractedAt(new \DateTimeImmutable());

        $xpEarned = 10;
        $pet->setXp($pet->getXp() + $xpEarned);
        $pet->setXpToNextLevel($pet->getXpToNextLevel() - $xpEarned);

        $this->checkLevelUp($pet);
        $this->checkDeath($pet);

        $careAction = $this->careActionService->logAction($pet, $performer, ActionTypeEnum::PLAY, $effectValue, $xpEarned);

        $this->entityManager->flush();

        return $careAction;
    }

    // Heal pet
    public function healPet(Pet $pet, User $performer, int $effectValue = 30): CareAction
    {
        if (!$pet->isAlive() && $pet->getHealth() <= 0) {
            throw new \Exception('Cannot heal a dead pet');
        }

        $pet->setHealth($pet->getHealth() + $effectValue);
        $pet->setLastInteractedAt(new \DateTimeImmutable());

        $xpEarned = 10;
        $pet->setXp($pet->getXp() + $xpEarned);
        $pet->setXpToNextLevel($pet->getXpToNextLevel() - $xpEarned);

        $this->checkLevelUp($pet);
        $this->checkDeath($pet);

        $careAction = $this->careActionService->logAction($pet, $performer, ActionTypeEnum::HEAL, $effectValue, $xpEarned);

        $this->entityManager->flush();

        return $careAction;
    }

    // Let pet sleep
    public function sleepPet(Pet $pet, User $performer, int $effectValue = 50): CareAction
    {
        if (!$pet->isAlive()) {
            throw new \Exception('Cannot make a dead pet sleep');
        }

        $pet->setEnergy($pet->getEnergy() + $effectValue);
        $pet->setLastInteractedAt(new \DateTimeImmutable());

        $xpEarned = 10;
        $pet->setXp($pet->getXp() + $xpEarned);
        $pet->setXpToNextLevel($pet->getXpToNextLevel() - $xpEarned);

        $this->checkLevelUp($pet);
        $this->checkDeath($pet);

        $careAction = $this->careActionService->logAction($pet, $performer, ActionTypeEnum::SLEEP, $effectValue, $xpEarned);

        $this->entityManager->flush();

        return $careAction;
    }



    // check if pet should level up
    private function checkLevelUp(Pet $pet): void
    {
        $currentLevelThreshold = $this->calculateXpToNextLevel($pet->getLevel());
        
        while ($pet->getXp() >= $currentLevelThreshold) {
            $overflow = $pet->getXp() - $currentLevelThreshold;
            
            $pet->setLevel($pet->getLevel() + 1);
            $pet->setXp($overflow);
            
            $currentLevelThreshold = $this->calculateXpToNextLevel($pet->getLevel());
        }
        
        $pet->setXpToNextLevel($currentLevelThreshold - $pet->getXp());
    }



    // Check if pet has died
    private function checkDeath(Pet $pet): void
    {
        if ($pet->getHealth() <= 0) {
            $pet->setIsAlive(false);
        }
    }

    // Delete a pet
    public function deletePet(Pet $pet): void
    {
        $this->entityManager->remove($pet);
        $this->entityManager->flush();
    }

    // Use an item on a pet
    public function useItemOnPet(Pet $pet, User $performer, Item $item): CareAction
    {
        if (!$pet->isAlive()) {
            throw new \Exception('Cannot use item on a dead pet');
        }

        $itemType = $item->getType();
        $effectValue = $item->getEffectValue();
        $xpEarned = 10;

        switch ($itemType) {
            case ItemTypeEnum::FOOD:
                $pet->setHunger(min(100, $pet->getHunger() + $effectValue));
                $actionType = ActionTypeEnum::FEED;
                $statDelta = $effectValue;
                break;
            case ItemTypeEnum::TOY:
                $pet->setHappiness(min(100, $pet->getHappiness() + $effectValue));
                $pet->setEnergy(max(0, $pet->getEnergy() - $effectValue));
                $actionType = ActionTypeEnum::PLAY;
                $statDelta = $effectValue;
                break;
            case ItemTypeEnum::MEDICINE:
                $pet->setHealth(min(100, $pet->getHealth() + $effectValue));
                $actionType = ActionTypeEnum::HEAL;
                $statDelta = $effectValue;
                break;
            default:
                throw new \Exception('Item type not supported for pet interaction');
        }

        $pet->setLastInteractedAt(new \DateTimeImmutable());
        $pet->setXp($pet->getXp() + $xpEarned);
        $pet->setXpToNextLevel($pet->getXpToNextLevel() - $xpEarned);

        $this->checkLevelUp($pet);
        $this->checkDeath($pet);

        $careAction = $this->careActionService->logAction($pet, $performer, $actionType, $statDelta, $xpEarned);

        return $careAction;
    }
}
