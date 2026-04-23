<?php

namespace App\Security\Voter;

use App\Entity\Delegation;
use App\Entity\Pet;
use App\Entity\User;
use App\Enum\DelegationStatusEnum;
use App\Repository\DelegationRepository;
use App\Service\DelegationService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PetVoter extends Voter
{
    public const DELETE = 'DELETE';
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const FEED = 'FEED';
    public const PLAY = 'PLAY';
    public const HEAL = 'HEAL';
    public const SLEEP = 'SLEEP';
    public const BATHE = 'BATHE';
    public const USE_ITEM = 'USE_ITEM';

    private array $careActions = [self::FEED, self::PLAY, self::HEAL, self::SLEEP, self::BATHE, self::USE_ITEM];

    public function __construct(
        private readonly DelegationRepository $delegationRepository,
        private readonly DelegationService $delegationService,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Pet && in_array($attribute, [
            self::DELETE, self::VIEW, self::EDIT,
            self::FEED, self::PLAY, self::HEAL, self::SLEEP, self::BATHE, self::USE_ITEM
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $pet = $subject;

        // Propriétaire du pet peut tout faire
        if ($pet->getOwner()->getId() === $user->getId()) {
            return true;
        }

        // Pour VIEW et les soins, vérifier si une délégation active existe
        if ($attribute === self::VIEW || in_array($attribute, $this->careActions)) {
            return $this->hasActiveDelegation($pet, $user);
        }

        // Pour autres actions (DELETE, EDIT), seul le propriétaire peut
        return false;
    }

    /**
     * Vérifier si l'utilisateur a une délégation ACTIVE pour ce pet
     */
    private function hasActiveDelegation(Pet $pet, User $user): bool
    {
        // Chercher une délégation pour ce pet avec cet utilisateur comme caretaker
        $delegations = $this->delegationRepository->findBy([
            'pet' => $pet,
            'caretaker' => $user,
        ]);

        foreach ($delegations as $delegation) {
            // Vérifier que la délégation est active (en se basant sur les dates et le statut)
            if ($this->delegationService->calculateDelegationStatus($delegation) === DelegationStatusEnum::ACTIVE) {
                return true;
            }
        }

        return false;
    }
}
