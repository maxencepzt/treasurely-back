<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\TeamJoinRequest;
use App\Entity\User;
use App\Repository\TeamJoinRequestRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Les demandes envoyées par le joueur connecté, tous statuts, la plus récente d'abord.
 *
 * @implements ProviderInterface<TeamJoinRequest>
 */
final class MyTeamRequestsProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly TeamJoinRequestRepository $requests,
    ) {
    }

    /**
     * @return TeamJoinRequest[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        return $this->requests->findByUser($user);
    }
}
