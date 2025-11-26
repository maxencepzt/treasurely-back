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
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof TreasureHunt;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var TreasureHunt $treasureHunt */
        $treasureHunt = $subject;

        // Les admins ont tous les droits
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($treasureHunt, $user),
            self::EDIT => $this->canEdit($treasureHunt, $user),
            self::DELETE => $this->canDelete($treasureHunt, $user),
            default => false,
        };
    }

    private function canView(TreasureHunt $treasureHunt, User $user): bool
    {
        // Le propriétaire de la chasse peut toujours voir
        if ($treasureHunt->getOwner() === $user) {
            return true;
        }

        $designerTeam = $treasureHunt->getDesignerTeam();

        // Le propriétaire de l'équipe de design peut voir
        if ($designerTeam->getOwner() === $user) {
            return true;
        }

        // Un membre de l'équipe de design peut voir uniquement si la chasse est ouverte ou fermée
        // (pas en brouillon)
        if ($designerTeam->hasMember($user)) {
            return in_array($treasureHunt->getStatus(), [
                TreasureHunt::STATE_OPENED,
                TreasureHunt::STATE_CLOSED,
            ], true);
        }

        // Sinon, accès refusé
        return false;
    }

    private function canEdit(TreasureHunt $treasureHunt, User $user): bool
    {
        // Le propriétaire de la chasse peut modifier
        if ($treasureHunt->getOwner() === $user) {
            return true;
        }

        $designerTeam = $treasureHunt->getDesignerTeam();

        // Le propriétaire de l'équipe de design peut modifier
        if ($designerTeam->getOwner() === $user) {
            return true;
        }

        // Les autres membres ne peuvent pas modifier
        return false;
    }

    private function canDelete(TreasureHunt $treasureHunt, User $user): bool
    {
        // Le propriétaire de la chasse peut supprimer
        if ($treasureHunt->getOwner() === $user) {
            return true;
        }

        $designerTeam = $treasureHunt->getDesignerTeam();

        // Les autres membres ne peuvent pas supprimer
        return false;
    }
}
