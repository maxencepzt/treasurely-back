<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\JoinTeamInput;
use App\Entity\PlayerTeam;
use App\Entity\User;
use App\Repository\PlayerTeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Rejoindre une équipe de joueurs par son code : le code vaut invitation, l'entrée est
 * immédiate. Un code inconnu ne dit rien de plus qu'« inconnu ».
 *
 * @implements ProcessorInterface<JoinTeamInput, PlayerTeam>
 */
final class JoinTeamProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly PlayerTeamRepository $teams,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PlayerTeam
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        $team = $this->teams->findOneBy(['code' => trim($data->code)])
            ?? throw new NotFoundHttpException('Aucune équipe ne porte ce code.');
        if ($team->hasMember($user)) {
            throw new ConflictHttpException('Vous êtes déjà membre de cette équipe.');
        }

        $team->addMember($user);
        $this->entityManager->flush();

        return $team;
    }
}
