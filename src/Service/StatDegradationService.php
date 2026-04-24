<?php

namespace App\Service;

use App\Entity\Pet;
use Doctrine\ORM\EntityManagerInterface;

class StatDegradationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    // degrade pet stats based on time passed since last degradation
    public function degradeStats(Pet $pet): void
    {
        $now = new \DateTimeImmutable();
        $lastDegradation = $pet->getLastStatsDegradationAt() ?? $pet->getBornAt();
        
        $interval = $now->diff($lastDegradation);
        $hoursPassed = $interval->days * 24 + $interval->h;

        if ($hoursPassed === 0) {
            return;
        }

        $hungerDecrease = 5 * $hoursPassed;
        $happinessDecrease = 5 * $hoursPassed;
        $energyDecrease = 2 * $hoursPassed;

        $pet->setHunger($pet->getHunger() - $hungerDecrease);
        $pet->setHappiness($pet->getHappiness() - $happinessDecrease);
        $pet->setEnergy($pet->getEnergy() - $energyDecrease);

        if ($pet->getHappiness() === 0 || $pet->getHunger() === 0 || $pet->getEnergy() === 0) {
            $healthDecrease = 5 * $hoursPassed;
            $pet->setHealth($pet->getHealth() - $healthDecrease);
        }

        $pet->setLastStatsDegradationAt($now);
        $this->entityManager->flush();
    }
}
