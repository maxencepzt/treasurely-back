<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\TeamJoinRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Le propriétaire refuse : la demande reste, refusée, et bloque une nouvelle demande tant
 * qu'il ne l'efface pas.
 *
 * @implements ProcessorInterface<TeamJoinRequest, TeamJoinRequest>
 */
final class RefuseJoinRequestProcessor implements ProcessorInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TeamJoinRequest
    {
        if (!$data->isPending()) {
            throw new ConflictHttpException('Cette demande a déjà été traitée.');
        }

        $data->refuse();
        $this->entityManager->flush();

        return $data;
    }
}
