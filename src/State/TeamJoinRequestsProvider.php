<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\PlayerTeam;
use App\Entity\TeamJoinRequest;
use App\Repository\TeamJoinRequestRepository;
use App\Repository\TeamRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Les demandes en attente d'une équipe de joueurs, pour son propriétaire.
 *
 * @implements ProviderInterface<TeamJoinRequest>
 */
final class TeamJoinRequestsProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly TeamRepository $teams,
        private readonly TeamJoinRequestRepository $requests,
    ) {
    }

    /**
     * @return TeamJoinRequest[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $team = $this->teams->find($uriVariables['id'] ?? 0);
        if (!$team instanceof PlayerTeam) {
            throw new NotFoundHttpException('Équipe de joueurs introuvable.');
        }
        if ($team->getOwner() !== $this->security->getUser()) {
            throw new AccessDeniedHttpException('Seul le propriétaire de l\'équipe lit ses demandes.');
        }

        return $this->requests->findPending($team);
    }
}
