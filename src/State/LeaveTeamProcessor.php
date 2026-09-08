<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Quitter une équipe. Le propriétaire ne la quitte pas : il la supprime, elle n'existe pas
 * sans lui.
 *
 * @implements ProcessorInterface<Team, null>
 */
final class LeaveTeamProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        if ($data->getOwner() === $user) {
            throw new ConflictHttpException('Le créateur ne peut pas quitter son équipe : il peut la supprimer.');
        }
        if (!$data->hasMember($user)) {
            throw new ConflictHttpException("Vous n'êtes pas membre de cette équipe.");
        }

        $data->removeMember($user);
        $this->entityManager->flush();

        return null;
    }
}
