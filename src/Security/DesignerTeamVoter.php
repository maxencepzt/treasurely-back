<?php

namespace App\Security;

use App\Entity\DesignerTeam;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, DesignerTeam>
 */
final class DesignerTeamVoter extends Voter
{
    public const string VIEW = 'DESIGNER_TEAM_VIEW';
    public const string EDIT = 'DESIGNER_TEAM_EDIT';
    public const string DELETE = 'DESIGNER_TEAM_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof DesignerTeam;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var DesignerTeam $designerTeam */
        $designerTeam = $subject;

        // Les admins ont tous les droits
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($designerTeam, $user),
            self::EDIT => $this->canEdit($designerTeam, $user),
            self::DELETE => $this->canDelete($designerTeam, $user),
            default => false,
        };
    }

    private function canView(DesignerTeam $designerTeam, User $user): bool
    {
        // Un utilisateur peut voir une équipe s'il en est membre ou propriétaire
        return $designerTeam->getOwner() === $user
            || $designerTeam->hasMember($user);
    }

    private function canEdit(DesignerTeam $designerTeam, User $user): bool
    {
        // Seul le propriétaire peut modifier l'équipe
        return $designerTeam->getOwner() === $user;
    }

    private function canDelete(DesignerTeam $designerTeam, User $user): bool
    {
        // Seul le propriétaire peut supprimer l'équipe
        return $designerTeam->getOwner() === $user;
    }
}
