<?php

namespace App\Repository;

use App\Entity\CareAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CareAction>
 */
class CareActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CareAction::class);
    }

    public function findByPet($pet)
    {
        return $this->findBy(['pet' => $pet], ['performedAt' => 'DESC']);
    }
}
