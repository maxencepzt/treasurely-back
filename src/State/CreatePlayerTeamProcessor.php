<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\PlayerTeamInput;
use App\Entity\PlayerTeam;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Créer une équipe de joueurs : le créateur en devient le propriétaire et le premier membre,
 * le code de jointure est tiré au sort et ne se choisit pas.
 *
 * @implements ProcessorInterface<PlayerTeamInput, PlayerTeam>
 */
final class CreatePlayerTeamProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PlayerTeam
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        $team = (new PlayerTeam())
            ->setName($data->name)
            ->setDescription($data->description)
            ->setOwner($user)
            ->setCode(PlayerTeam::generateCode())
            ->addMember($user);
        $this->entityManager->persist($team);
        $this->entityManager->flush();

        return $team;
    }
}
