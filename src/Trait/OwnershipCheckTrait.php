<?php

namespace App\Trait;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Ce trait doit être utilisé dans un contrôleur Symfony qui étend AbstractController
 * pour avoir accès à la méthode getUser().
 */
trait OwnershipCheckTrait
{
    /**
     * Vérifie si l'utilisateur connecté est admin ou propriétaire de l'entité.
     * Lance une exception si l'utilisateur n'a pas les droits.
     */
    private function checkOwnership(object $entity, string $ownerGetter = 'getOwner'): void
    {
        /** @var UserInterface|null $user */
        $user = $this->getUser();
        $roles = $user?->getRoles() ?? [];

        // Si admin, accès autorisé
        if (in_array('ROLE_ADMIN', $roles)) {
            return;
        }

        // Si l'entité est l'utilisateur lui-même
        if ($entity === $user) {
            return;
        }

        // Si l'entité a un owner et que c'est l'utilisateur connecté
        if (method_exists($entity, $ownerGetter)) {
            $owner = $entity->$ownerGetter();
            if ($owner === $user) {
                return;
            }
        }

        // Sinon, accès refusé
        throw new AccessDeniedHttpException('You do not have permission to perform this action');
    }

    /**
     * Cette méthode est fournie par AbstractController.
     */
    abstract protected function getUser(): ?UserInterface;
}
