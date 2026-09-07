<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\RiddleAttempt;
use App\Entity\ParticipateHunt;
use App\Entity\ParticipateRiddle;
use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Repository\ParticipateHuntRepository;
use App\Repository\ParticipateRiddleRepository;
use App\Repository\RiddleRepository;
use App\Service\ScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Seule autorité sur trois questions : la proposition est-elle juste, combien vaut-elle,
 * l'énigme est-elle résolue. Une réussite fait avancer la participation à la chasse.
 * Le client n'écrit jamais un score.
 *
 * @implements ProcessorInterface<RiddleAttempt, ParticipateRiddle>
 */
final class SolveRiddleProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly ScoreCalculator $scoreCalculator,
        private readonly ParticipateHuntRepository $huntParticipations,
        private readonly ParticipateRiddleRepository $riddleParticipations,
        private readonly RiddleRepository $riddles,
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

        $hunt = $riddle->getHunt();
        if (!$hunt?->isOpened()) {
            throw new ConflictHttpException("Cette chasse n'est pas ouverte.");
        }
        if (!$this->scoreCalculator->canUserPlay($user, $hunt)) {
            throw new AccessDeniedHttpException('Vous ne pouvez pas jouer une chasse que vous avez conçue.');
        }
        $progress = $this->huntParticipations->findOneBy(['hunter' => $user, 'hunt' => $hunt])
            ?? throw new ConflictHttpException('Rejoignez la chasse avant de jouer.');
        if ($progress->isFinished() || $progress->getCurrentRiddle() !== $riddle) {
            throw new ConflictHttpException("Ce n'est pas l'énigme en cours.");
        }
        // La ligne naît à la consultation de l'énigme : c'est elle qui date le départ du chronomètre.
        $participation = $this->riddleParticipations->findOneBy(['hunter' => $user, 'riddle' => $riddle])
            ?? throw new ConflictHttpException("Consultez l'énigme avant d'y répondre.");

        try {
            $correct = $riddle->accepts($data);
        } catch (\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        $now = new \DateTimeImmutable();
        $participation->incrementAttempts()->setLastParticipate(\DateTime::createFromImmutable($now));
        $progress->setLastParticipate($now);

        if ($correct) {
            $participation->setFinishTime($now);
            $participation->setScore(
                $participation->getAttempts() <= $riddle->getMaxScoringAttempts()
                    ? $this->scoreCalculator->calculateScore($participation->getStartTime(), $now, $riddle->getDifficulty())
                    : 0
            );
            $this->entityManager->flush();
            $this->advance($progress, $hunt, $user);
        }

        $this->entityManager->flush();

        return $participation;
    }

    /**
     * Passe à l'énigme suivante, ou clôt la chasse après la dernière, et recalcule les
     * agrégats de la participation depuis les énigmes résolues, une fois celles-ci en base.
     */
    private function advance(ParticipateHunt $progress, TreasureHunt $hunt, User $user): void
    {
        $ordered = $this->riddles->findByTreasureHunt($hunt);
        $position = array_search($progress->getCurrentRiddle(), $ordered, true);
        $next = false === $position ? null : ($ordered[$position + 1] ?? null);

        if (null === $next) {
            $progress->setFinished(true);
        } else {
            $progress->setCurrentRiddle($next);
        }

        $totals = $this->scoreCalculator->totalScorePerHunt($hunt, $user);
        $progress->setScore($totals['score'])->setTime($totals['time']);
    }
}
