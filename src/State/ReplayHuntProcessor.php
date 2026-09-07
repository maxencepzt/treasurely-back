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
 * Refaire une chasse terminée : la participation repart de la première énigme, score et
 * temps à zéro, les chronomètres des énigmes effacés. L'ancien score n'est pas conservé,
 * le prochain le remplace.
 *
 * @implements ProcessorInterface<ParticipateHunt|null, ParticipateHunt>
 */
final class ReplayHuntProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly RiddleRepository $riddles,
        private readonly ParticipateRiddleRepository $riddleParticipations,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ParticipateHunt
    {
        // API Platform ne renvoie pas 404 de lui-même sur un POST dont la cible n'existe pas.
        if (!$data instanceof ParticipateHunt) {
            throw new NotFoundHttpException('Participation introuvable.');
        }
        $user = $this->security->getUser();
        \assert($user instanceof User);
        if ($data->getHunter() !== $user) {
            throw new AccessDeniedHttpException("Cette participation n'est pas la vôtre.");
        }
        if (!$data->isFinished()) {
            throw new ConflictHttpException('Cette chasse est encore en cours.');
        }
        $hunt = $data->getHunt();
        if (!$hunt?->isOpened()) {
            throw new ConflictHttpException("Cette chasse n'est pas ouverte.");
        }
        $riddles = $this->riddles->findByTreasureHunt($hunt);
        $first = $riddles[0] ?? throw new ConflictHttpException("Cette chasse n'a pas encore d'énigme.");

        // Suppression entité par entité, pour que les écouteurs recalculent les statistiques du joueur.
        foreach ($this->riddleParticipations->findBy(['hunter' => $user, 'riddle' => $riddles]) as $participation) {
            $this->entityManager->remove($participation);
        }
        $data->setFinished(false)
            ->setScore(0)
            ->setTime(0)
            ->setCurrentRiddle($first)
            ->setLastParticipate(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $data;
    }
}
