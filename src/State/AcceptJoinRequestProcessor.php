<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\TeamJoinRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Le propriétaire accepte : le joueur entre dans l'équipe, la demande garde la trace.
 *
 * @implements ProcessorInterface<TeamJoinRequest, TeamJoinRequest>
 */
final class AcceptJoinRequestProcessor implements ProcessorInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TeamJoinRequest
    {
        if (!$data->isPending()) {
            throw new ConflictHttpException('Cette demande a déjà été traitée.');
        }

        $data->accept();
        $data->getTeam()->addMember($data->getUser());
        $this->entityManager->flush();

        return $data;
    }
}
