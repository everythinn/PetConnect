<?php

namespace App\Repository;

use App\Entity\Delegation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Delegation>
 */
class DelegationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Delegation::class);
    }

    public function findByOwner($owner)
    {
        return $this->findBy(['owner' => $owner], ['startDate' => 'DESC']);
    }

    public function findByCaretaker($caretaker)
    {
        return $this->findBy(['caretaker' => $caretaker], ['startDate' => 'DESC']);
    }
}
