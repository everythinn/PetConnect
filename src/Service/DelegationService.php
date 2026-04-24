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

    // create a delegation
    public function createDelegation(Pet $pet, User $owner, User $caretaker, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): Delegation
    {
        if ($endDate <= $startDate) {
            throw new \Exception('End date must be after start date');
        }

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

    // find initial status for delegation basd on start date
    private function calculateInitialStatus(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): DelegationStatusEnum
    {
        $nowDate = (new \DateTimeImmutable())->format('Y-m-d');
        $startStr = $startDate->format('Y-m-d');
        $endStr = $endDate->format('Y-m-d');

        if ($startStr <= $nowDate) {
            if ($endStr < $nowDate) {
                return DelegationStatusEnum::EXPIRED;
            }
            return DelegationStatusEnum::ACTIVE;
        }

        return DelegationStatusEnum::PENDING;
    }

    // same + time
    public function calculateDelegationStatus(Delegation $delegation): DelegationStatusEnum
    {
        if ($delegation->getStatus() === DelegationStatusEnum::REVOKED) {
            return DelegationStatusEnum::REVOKED;
        }

        $nowDate = (new \DateTimeImmutable())->format('Y-m-d');
        $startDate = $delegation->getStartDate()->format('Y-m-d');
        $endDate = $delegation->getEndDate()->format('Y-m-d');

        if ($nowDate > $endDate) {
            return DelegationStatusEnum::EXPIRED;
        }

        if ($nowDate < $startDate) {
            return DelegationStatusEnum::PENDING;
        }

        return DelegationStatusEnum::ACTIVE;
    }

    // update status based on time
    public function updateDelegationStatus(Delegation $delegation): void
    {
        $newStatus = $this->calculateDelegationStatus($delegation);

        if ($delegation->getStatus() !== $newStatus) {
            $delegation->setStatus($newStatus);
            $this->entityManager->flush();
        }
    }

    // revoke delegation
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

    // check if delegation is active
    public function isDelegationActive(Delegation $delegation): bool
    {
        return $this->calculateDelegationStatus($delegation) === DelegationStatusEnum::ACTIVE;
    }

    // get active delegation for pet and user
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
