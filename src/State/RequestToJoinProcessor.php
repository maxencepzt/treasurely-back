<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\PlayerTeam;
use App\Entity\Team;
use App\Entity\TeamJoinRequest;
use App\Entity\User;
use App\Enum\JoinRequestStatus;
use App\Repository\TeamJoinRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Demander à rejoindre une équipe de joueurs vue dans l'annuaire. Une demande par joueur et
 * par équipe : en attente, acceptée ou refusée, elle bloque la suivante.
 *
 * @implements ProcessorInterface<Team, TeamJoinRequest>
 */
final class RequestToJoinProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly TeamJoinRequestRepository $requests,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TeamJoinRequest
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);
        if (!$data instanceof PlayerTeam) {
            throw new NotFoundHttpException('Équipe de joueurs introuvable.');
        }
        if ($data->hasMember($user)) {
            throw new ConflictHttpException('Vous êtes déjà membre de cette équipe.');
        }

        $existing = $this->requests->findOneBy(['team' => $data, 'user' => $user]);
        if (null !== $existing) {
            $message = match ($existing->getStatus()) {
                JoinRequestStatus::PENDING => 'Votre demande est déjà en attente.',
                JoinRequestStatus::ACCEPTED => 'Votre demande a déjà été acceptée.',
                JoinRequestStatus::REFUSED => 'Votre demande a été refusée.',
            };
            throw new ConflictHttpException($message);
        }

        $request = new TeamJoinRequest($data, $user);
        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return $request;
    }
}
