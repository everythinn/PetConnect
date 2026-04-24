<?php

namespace App\Service;

use App\Entity\CareAction;
use App\Entity\Pet;
use App\Entity\User;
use App\Enum\ActionTypeEnum;
use App\Repository\CareActionRepository;
use Doctrine\ORM\EntityManagerInterface;

class CareActionService
{
    public function __construct(
        private readonly CareActionRepository $careActionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    // log a care action
    public function logAction(Pet $pet, User $performer, ActionTypeEnum $actionType, int $statDelta, int $xpEarned): CareAction
    {
        $careAction = new CareAction();
        $careAction->setPet($pet);
        $careAction->setPerformer($performer);
        $careAction->setActionType($actionType);
        $careAction->setStatDelta($statDelta);
        $careAction->setXpEarned($xpEarned);

        $this->entityManager->persist($careAction);

        return $careAction;
    }

    // get care history for a pet
    public function getPetCareHistory(Pet $pet, int $limit = 10): array
    {
        return $this->careActionRepository->findBy(
            ['pet' => $pet],
            ['performedAt' => 'DESC'],
            $limit
        );
    }
}
