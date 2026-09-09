<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\ParticipateHunt;
use App\Entity\User;
use App\Repository\ParticipateRiddleRepository;
use App\Repository\RiddleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Quitter une chasse commencée : la participation et les chronomètres de ses énigmes
 * disparaissent, comme si le joueur ne s'était jamais inscrit. Une chasse terminée ne se
 * quitte pas : son score reste au classement, et « Rejouer » existe pour la refaire.
 *
 * @implements ProcessorInterface<ParticipateHunt|null, null>
 */
final class LeaveHuntProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly RiddleRepository $riddles,
        private readonly ParticipateRiddleRepository $riddleParticipations,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (!$data instanceof ParticipateHunt) {
            throw new NotFoundHttpException('Participation introuvable.');
        }
        $user = $this->security->getUser();
        \assert($user instanceof User);
        if ($data->getHunter() !== $user) {
            throw new AccessDeniedHttpException("Cette participation n'est pas la vôtre.");
        }
        if ($data->isFinished()) {
            throw new ConflictHttpException('Cette chasse est terminée : son score reste au classement.');
        }

        $hunt = $data->getHunt();
        if (null !== $hunt) {
            // Suppression entité par entité, pour que les écouteurs recalculent les statistiques du joueur.
            foreach ($this->riddleParticipations->findBy(['hunter' => $user, 'riddle' => $this->riddles->findByTreasureHunt($hunt)]) as $participation) {
                $this->entityManager->remove($participation);
            }
        }
        $this->entityManager->remove($data);
        $this->entityManager->flush();

        return null;
    }
}
