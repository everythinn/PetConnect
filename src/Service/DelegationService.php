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
     */
    public function createDelegation(Pet $pet, User $owner, User $caretaker, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): Delegation
    {
        if ($endDate <= $startDate) {
            throw new \Exception('End date must be after start date');
        }

        $delegation = new Delegation();
        $delegation->setPet($pet);
        $delegation->setOwner($owner);
        $delegation->setCaretaker($caretaker);
        $delegation->setStartDate($startDate);
        $delegation->setEndDate($endDate);
        $delegation->setStatus(DelegationStatusEnum::PENDING);

        $this->entityManager->persist($delegation);
        $this->entityManager->flush();

        return $delegation;
    }

    /**
     * Accept a delegation (caretaker)
     */
    public function acceptDelegation(Delegation $delegation): void
    {
        if ($delegation->getStatus() !== DelegationStatusEnum::PENDING) {
            throw new \Exception('Only pending delegations can be accepted');
        }

        $delegation->setStatus(DelegationStatusEnum::ACTIVE);
        $this->entityManager->flush();
    }

    /**
     * Revoke a delegation (owner)
     */
    public function revokeDelegation(Delegation $delegation): void
    {
        if ($delegation->getStatus() === DelegationStatusEnum::EXPIRED || $delegation->getStatus() === DelegationStatusEnum::REVOKED) {
            throw new \Exception('Cannot revoke an already expired or revoked delegation');
        }

        $delegation->setStatus(DelegationStatusEnum::REVOKED);
        $this->entityManager->flush();
    }

    /**
     * Expire a delegation
     */
    public function expireDelegation(Delegation $delegation): void
    {
        if ($delegation->getStatus() !== DelegationStatusEnum::ACTIVE) {
            throw new \Exception('Only active delegations can expire');
        }

        $delegation->setStatus(DelegationStatusEnum::EXPIRED);
        $this->entityManager->flush();
    }

    /**
     * Check if a delegation is active
     */
    public function isDelegationActive(Delegation $delegation): bool
    {
        if ($delegation->getStatus() !== DelegationStatusEnum::ACTIVE) {
            return false;
        }

        $now = new \DateTimeImmutable();

        return $now >= $delegation->getStartDate() && $now <= $delegation->getEndDate();
    }

    /**
     * Get active delegation for pet and user
     */
    public function getActiveDelegationForCaretaker(Pet $pet, User $caretaker): ?Delegation
    {
        $delegations = $this->delegationRepository->findBy([
            'pet' => $pet,
            'caretaker' => $caretaker,
            'status' => DelegationStatusEnum::ACTIVE,
        ]);

        if (empty($delegations)) {
            return null;
        }

        $now = new \DateTimeImmutable();

        foreach ($delegations as $delegation) {
            if ($now >= $delegation->getStartDate() && $now <= $delegation->getEndDate()) {
                return $delegation;
            }
        }

        return null;
    }

    /**
     * Auto-expire delegations that have passed their end date
     */
    public function autoExpireExpiredDelegations(): int
    {
        $now = new \DateTimeImmutable();
        $expired = $this->delegationRepository->createQueryBuilder('d')
            ->where('d.status = :status')
            ->andWhere('d.endDate < :now')
            ->setParameter('status', DelegationStatusEnum::ACTIVE)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        foreach ($expired as $delegation) {
            $this->expireDelegation($delegation);
        }

        return count($expired);
    }
}
