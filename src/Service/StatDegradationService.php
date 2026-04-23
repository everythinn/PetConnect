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

    /**
     * Degrade pet stats based on time passed since last degradation
     * - Hunger: +5 points per hour (pet gets hungrier)
     * - Happiness: -5 points per hour
     * - Energy: -2 points per hour
     */
    public function degradeStats(Pet $pet): void
    {
        $now = new \DateTimeImmutable();
        $lastDegradation = $pet->getLastStatsDegradationAt() ?? $pet->getBornAt();
        
        // Calculate hours passed
        $interval = $now->diff($lastDegradation);
        $hoursPassed = $interval->days * 24 + $interval->h;

        if ($hoursPassed === 0) {
            return;
        }

        // Apply degradation based on points per hour
        // Note: 100 means full/good, 0 means empty/bad. Thus hunger should DECREASE over time.
        $hungerDecrease = 5 * $hoursPassed;
        $happinessDecrease = 5 * $hoursPassed;
        $energyDecrease = 2 * $hoursPassed;

        // Apply changes (setters clamp values between 0-100)
        $pet->setHunger($pet->getHunger() - $hungerDecrease);
        $pet->setHappiness($pet->getHappiness() - $happinessDecrease);
        $pet->setEnergy($pet->getEnergy() - $energyDecrease);

        // Apply health damage if critical stats hit 0
        if ($pet->getHappiness() === 0 || $pet->getHunger() === 0 || $pet->getEnergy() === 0) {
            $healthDecrease = 5 * $hoursPassed;
            $pet->setHealth($pet->getHealth() - $healthDecrease);
        }

        // Update last degradation timestamp
        $pet->setLastStatsDegradationAt($now);

        // Persist changes
        $this->entityManager->flush();
    }
}
