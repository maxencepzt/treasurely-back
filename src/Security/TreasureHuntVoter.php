<?php

namespace App\Security;

use App\Entity\TreasureHunt;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, TreasureHunt>
 */
final class TreasureHuntVoter extends Voter
{
    public const string VIEW = 'TREASURE_HUNT_VIEW';
    public const string EDIT = 'TREASURE_HUNT_EDIT';
    public const string DELETE = 'TREASURE_HUNT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof TreasureHunt;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        /** @var TreasureHunt $treasureHunt */
        $treasureHunt = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($treasureHunt, $user),
            self::EDIT => $this->canEdit($treasureHunt, $user),
            self::DELETE => $treasureHunt->getOwner() === $user,
            default => false,
        };
    }

    /**
     * Le propriétaire de la chasse et celui de l'équipe voient tout ; un membre de
     * l'équipe ne voit pas les brouillons.
     */
    private function canView(TreasureHunt $treasureHunt, User $user): bool
    {
        if ($this->canEdit($treasureHunt, $user)) {
            return true;
        }

        $designerTeam = $treasureHunt->getDesignerTeam();

        return null !== $designerTeam
            && $designerTeam->hasMember($user)
            && TreasureHunt::STATE_DRAFT !== $treasureHunt->getStatus();
    }

    private function canEdit(TreasureHunt $treasureHunt, User $user): bool
    {
        if ($treasureHunt->getOwner() === $user) {
            return true;
        }

        return $treasureHunt->getDesignerTeam()?->getOwner() === $user;
    }
}
