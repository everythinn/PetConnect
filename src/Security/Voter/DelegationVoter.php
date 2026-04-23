<?php

namespace App\Security\Voter;

use App\Entity\Delegation;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DelegationVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const REVOKE = 'REVOKE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Delegation && in_array($attribute, [
            self::VIEW, self::REVOKE
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $delegation = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($delegation, $user),
            self::REVOKE => $this->canRevoke($delegation, $user),
            default => false,
        };
    }

    /**
     * Voir une délégation : owner OU caretaker
     */
    private function canView(Delegation $delegation, User $user): bool
    {
        return $delegation->getOwner()->getId() === $user->getId() ||
               $delegation->getCaretaker()->getId() === $user->getId();
    }

    /**
     * Révoquer une délégation : owner SEULEMENT
     */
    private function canRevoke(Delegation $delegation, User $user): bool
    {
        return $delegation->getOwner()->getId() === $user->getId();
    }
}
