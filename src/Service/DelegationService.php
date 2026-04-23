<?php

namespace App\Service;

use App\Entity\Delegation;
use App\Entity\Pet;
use App\Entity\User;
use App\Enum\DelegationStatusEnum;
use App\Repository\DelegationRepository;
use Doctrine\ORM\EntityManagerInterface;

class DelegationService
{
    public function __construct(
        private readonly DelegationRepository $delegationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Create a delegation
     * If start date is today or earlier, delegation is created as ACTIVE
     */
    public function createDelegation(Pet $pet, User $owner, User $caretaker, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): Delegation
    {
        if ($endDate <= $startDate) {
            throw new \Exception('End date must be after start date');
        }

        // Determine initial status based on start date
        $initialStatus = $this->calculateInitialStatus($startDate, $endDate);

        $delegation = new Delegation();
        $delegation->setPet($pet);
        $delegation->setOwner($owner);
        $delegation->setCaretaker($caretaker);
        $delegation->setStartDate($startDate);
        $delegation->setEndDate($endDate);
        $delegation->setStatus($initialStatus);

        $this->entityManager->persist($delegation);
        $this->entityManager->flush();

        return $delegation;
    }

    /**
     * Calculate initial status for a new delegation based on dates
     */
    private function calculateInitialStatus(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): DelegationStatusEnum
    {
        $nowDate = (new \DateTimeImmutable())->format('Y-m-d');
        $startStr = $startDate->format('Y-m-d');
        $endStr = $endDate->format('Y-m-d');

        // If start date is today or earlier, check if end date is still in future
        if ($startStr <= $nowDate) {
            // Check if end date has already passed
            if ($endStr < $nowDate) {
                return DelegationStatusEnum::EXPIRED;
            }
            return DelegationStatusEnum::ACTIVE;
        }

        return DelegationStatusEnum::PENDING;
    }

    /**
     * Calculate the appropriate status for a delegation based on current time
     */
    public function calculateDelegationStatus(Delegation $delegation): DelegationStatusEnum
    {
        // If explicitly revoked, stay revoked
        if ($delegation->getStatus() === DelegationStatusEnum::REVOKED) {
            return DelegationStatusEnum::REVOKED;
        }

        $nowDate = (new \DateTimeImmutable())->format('Y-m-d');
        $startDate = $delegation->getStartDate()->format('Y-m-d');
        $endDate = $delegation->getEndDate()->format('Y-m-d');

        // Check if delegation has passed its end date
        if ($nowDate > $endDate) {
            return DelegationStatusEnum::EXPIRED;
        }

        // Check if delegation hasn't started yet
        if ($nowDate < $startDate) {
            return DelegationStatusEnum::PENDING;
        }

        // Between start and end date (inclusive)
        return DelegationStatusEnum::ACTIVE;
    }

    /**
     * Update a delegation's status based on current time
     */
    public function updateDelegationStatus(Delegation $delegation): void
    {
        $newStatus = $this->calculateDelegationStatus($delegation);

        if ($delegation->getStatus() !== $newStatus) {
            $delegation->setStatus($newStatus);
            $this->entityManager->flush();
        }
    }

    /**
     * Revoke a delegation (owner)
     */
    public function revokeDelegation(Delegation $delegation): void
    {
        if ($delegation->getStatus() === DelegationStatusEnum::REVOKED) {
            throw new \Exception('Delegation is already revoked');
        }

        if ($delegation->getStatus() === DelegationStatusEnum::EXPIRED) {
            throw new \Exception('Cannot revoke an already expired delegation');
        }

        $delegation->setStatus(DelegationStatusEnum::REVOKED);
        $this->entityManager->flush();
    }

    /**
     * Check if a delegation is active (based on status and dates)
     */
    public function isDelegationActive(Delegation $delegation): bool
    {
        return $this->calculateDelegationStatus($delegation) === DelegationStatusEnum::ACTIVE;
    }

    /**
     * Get active delegation for pet and user
     */
    public function getActiveDelegationForCaretaker(Pet $pet, User $caretaker): ?Delegation
    {
        $delegations = $this->delegationRepository->findBy([
            'pet' => $pet,
            'caretaker' => $caretaker,
        ]);

        if (empty($delegations)) {
            return null;
        }

        foreach ($delegations as $delegation) {
            if ($this->calculateDelegationStatus($delegation) === DelegationStatusEnum::ACTIVE) {
                return $delegation;
            }
        }

        return null;
    }
}
