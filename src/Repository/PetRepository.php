<?php

namespace App\Repository;

use App\Entity\Pet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class PetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pet::class);
    }

    public function findByOwner($owner)
    {
        return $this->findBy(['owner' => $owner]);
    }

    public function findAliveByOwner($owner)
    {
        return $this->findBy(['owner' => $owner, 'isAlive' => true]);
    }
}
