<?php

namespace App\Service;

use App\Entity\CareAction;
use App\Entity\Pet;
use App\Entity\User;
use App\Enum\ActionTypeEnum;
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

    /**
     * Adopt a new pet
     */
    public function adoptPet(User $owner, string $name, SpeciesEnum $species): Pet
    {
        // Check if owner has reached max pets
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
        $pet->setXpToNextLevel(100);
        $pet->setHunger(50);
        $pet->setHappiness(50);
        $pet->setHealth(100);
        $pet->setEnergy(100);
        $pet->setIsAlive(true);

        $this->entityManager->persist($pet);
        $this->entityManager->flush();

        return $pet;
    }

    /**
     * Feed a pet
     */
    public function feedPet(Pet $pet, User $performer, int $effectValue = 20): CareAction
    {
        if (!$pet->isAlive()) {
            throw new \Exception('Cannot feed a dead pet');
        }

        $pet->setHunger($pet->getHunger() - $effectValue);
        $pet->setLastInteractedAt(new \DateTimeImmutable());

        $xpEarned = 10;
        $pet->setXp($pet->getXp() + $xpEarned);

        $this->checkLevelUp($pet);

        $careAction = $this->careActionService->logAction($pet, $performer, ActionTypeEnum::FEED, -$effectValue, $xpEarned);

        $this->entityManager->flush();

        return $careAction;
    }

    /**
     * Play with a pet
     */
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

        $xpEarned = 15;
        $pet->setXp($pet->getXp() + $xpEarned);

        $this->checkLevelUp($pet);

        $careAction = $this->careActionService->logAction($pet, $performer, ActionTypeEnum::PLAY, $effectValue, $xpEarned);

        $this->entityManager->flush();

        return $careAction;
    }

    /**
     * Heal a pet
     */
    public function healPet(Pet $pet, User $performer, int $effectValue = 30): CareAction
    {
        if (!$pet->isAlive() && $pet->getHealth() <= 0) {
            throw new \Exception('Cannot heal a dead pet');
        }

        $pet->setHealth($pet->getHealth() + $effectValue);
        $pet->setLastInteractedAt(new \DateTimeImmutable());

        $xpEarned = 20;
        $pet->setXp($pet->getXp() + $xpEarned);

        $this->checkLevelUp($pet);

        $careAction = $this->careActionService->logAction($pet, $performer, ActionTypeEnum::HEAL, $effectValue, $xpEarned);

        $this->entityManager->flush();

        return $careAction;
    }

    /**
     * Let pet sleep
     */
    public function sleepPet(Pet $pet, User $performer, int $effectValue = 40): CareAction
    {
        if (!$pet->isAlive()) {
            throw new \Exception('Cannot make a dead pet sleep');
        }

        $pet->setEnergy($pet->getEnergy() + $effectValue);
        $pet->setLastInteractedAt(new \DateTimeImmutable());

        $xpEarned = 5;
        $pet->setXp($pet->getXp() + $xpEarned);

        $this->checkLevelUp($pet);

        $careAction = $this->careActionService->logAction($pet, $performer, ActionTypeEnum::SLEEP, $effectValue, $xpEarned);

        $this->entityManager->flush();

        return $careAction;
    }

    /**
     * Bathe a pet
     */
    public function bathePet(Pet $pet, User $performer, int $effectValue = 25): CareAction
    {
        if (!$pet->isAlive()) {
            throw new \Exception('Cannot bathe a dead pet');
        }

        $pet->setHealth($pet->getHealth() + $effectValue);
        $pet->setHappiness($pet->getHappiness() + 10);
        $pet->setEnergy($pet->getEnergy() - 10);
        $pet->setLastInteractedAt(new \DateTimeImmutable());

        $xpEarned = 12;
        $pet->setXp($pet->getXp() + $xpEarned);

        $this->checkLevelUp($pet);

        $careAction = $this->careActionService->logAction($pet, $performer, ActionTypeEnum::BATHE, $effectValue, $xpEarned);

        $this->entityManager->flush();

        return $careAction;
    }

    /**
     * Check if pet should level up
     */
    private function checkLevelUp(Pet $pet): void
    {
        while ($pet->getXp() >= $pet->getXpToNextLevel()) {
            $pet->setLevel($pet->getLevel() + 1);
            $pet->setXp($pet->getXp() - $pet->getXpToNextLevel());
            $pet->setXpToNextLevel((int)($pet->getXpToNextLevel() * 1.1));
        }
    }

    /**
     * Delete a pet
     */
    public function deletePet(Pet $pet): void
    {
        $this->entityManager->remove($pet);
        $this->entityManager->flush();
    }
}
