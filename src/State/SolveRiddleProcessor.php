<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\RiddleAttempt;
use App\Entity\ParticipateRiddle;
use App\Entity\Riddle;
use App\Entity\User;
use App\Repository\ParticipateRiddleRepository;
use App\Service\ScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Seule autorité sur trois questions : la proposition est-elle juste, combien vaut-elle,
 * l'énigme est-elle résolue. Le client n'écrit jamais un score.
 *
 * @implements ProcessorInterface<RiddleAttempt, ParticipateRiddle>
 */
final class SolveRiddleProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly ScoreCalculator $scoreCalculator,
        private readonly ParticipateRiddleRepository $participations,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ParticipateRiddle
    {
        // API Platform ne renvoie pas 404 de lui-même sur un POST dont la cible n'existe pas.
        $riddle = $context['read_data'] ?? null;
        if (!$riddle instanceof Riddle) {
            throw new NotFoundHttpException('Énigme introuvable.');
        }
        $user = $this->security->getUser();
        \assert($user instanceof User);

        if (!$riddle->getHunt()?->isOpened()) {
            throw new ConflictHttpException("Cette chasse n'est pas ouverte.");
        }
        if (!$this->scoreCalculator->canUserParticipate($user, $riddle)) {
            throw new AccessDeniedHttpException('Vous ne pouvez pas jouer une chasse que vous avez conçue.');
        }

        // La ligne naît à la consultation de l'énigme : c'est elle qui date le départ du chronomètre.
        $participation = $this->participations->findOneBy(['hunter' => $user, 'riddle' => $riddle])
            ?? throw new ConflictHttpException("Consultez l'énigme avant d'y répondre.");
        if ($participation->isSolved()) {
            throw new ConflictHttpException('Cette énigme est déjà résolue.');
        }

        try {
            $correct = $riddle->accepts($data);
        } catch (\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        $now = new \DateTimeImmutable();
        $participation->incrementAttempts()->setLastParticipate(\DateTime::createFromImmutable($now));

        if ($correct) {
            $participation->setFinishTime($now);
            $participation->setScore(
                $participation->getAttempts() <= $riddle->getMaxScoringAttempts()
                    ? $this->scoreCalculator->calculateScore($participation->getStartTime(), $now, $riddle->getDifficulty())
                    : 0
            );
        }

        $this->entityManager->flush();

        return $participation;
    }
}
