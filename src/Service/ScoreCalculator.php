<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ParticipateRiddle;
use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use App\Entity\User;

class ScoreCalculator
{
    /**
     * Calcule le score en fonction du temps écoulé et de la difficulté.
     * Formule : difficulty * 1000 * exp(-0.001 * temps_en_secondes)
     * Plus le temps est court, plus le score est élevé (décroissance exponentielle).
     */
    public function calculateScore(\DateTimeImmutable $startTime, \DateTimeImmutable $endTime, int $difficulty): int
    {
        $time = $endTime->getTimestamp() - $startTime->getTimestamp();

        return (int) ($difficulty * (1000 * exp(-0.001 * abs($time))));
    }

    public function canUserParticipate(User $user, Riddle $riddle): bool
    {
        $hunt = $riddle->getHunt();

        if (!$hunt) {
            return false;
        }

        $owner = $hunt->getOwner();

        // Le propriétaire de la chasse ne peut pas participer
        if ($owner && $user === $owner) {
            return false;
        }

        // Les membres de l'équipe ne peuvent pas participer
        $team = $hunt->getTeam();
        if ($team) {
            foreach ($team->getMembers() as $member) {
                if ($user === $member) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Vérifie si une participation appartient à l'utilisateur et est terminée.
     */
    private function isCompletedParticipationForUser(ParticipateRiddle $participation, User $user): bool
    {
        return $participation->getHunter() === $user && null !== $participation->getFinishTime();
    }

    /**
     * Récupère toutes les participations terminées de l'utilisateur pour une chasse donnée.
     *
     * @return ParticipateRiddle[]
     */
    private function getCompletedParticipations(TreasureHunt $hunt, User $user): array
    {
        $completedParticipations = [];

        foreach ($hunt->getRiddles() as $riddle) {
            foreach ($riddle->getParticipateRiddles() as $participation) {
                if ($this->isCompletedParticipationForUser($participation, $user)) {
                    $completedParticipations[] = $participation;
                }
            }
        }

        return $completedParticipations;
    }

    /**
     * Calcule le score total à partir des participations.
     *
     * @param ParticipateRiddle[] $participations
     */
    private function calculateTotalScore(array $participations): int
    {
        $totalScore = 0;

        foreach ($participations as $participation) {
            $totalScore += $participation->getScore();
        }

        return $totalScore;
    }

    /**
     * Calcule le temps total passé à partir des participations.
     *
     * @param ParticipateRiddle[] $participations
     */
    private function calculateTotalTime(array $participations): int
    {
        $totalTime = 0;

        foreach ($participations as $participation) {
            $finishTime = $participation->getFinishTime();
            if ($finishTime) {
                $totalTime += $finishTime->getTimestamp() - $participation->getStartTime()->getTimestamp();
            }
        }

        return $totalTime;
    }

    /**
     * Détermine la date de participation la plus récente.
     *
     * @param ParticipateRiddle[] $participations
     */
    private function findMostRecentParticipationDate(array $participations): \DateTimeImmutable
    {
        $mostRecent = new \DateTimeImmutable('@0'); // Epoch time comme valeur par défaut

        foreach ($participations as $participation) {
            // Conversion DateTime -> DateTimeImmutable pour comparaison cohérente
            $participateDateTime = \DateTimeImmutable::createFromMutable($participation->getLastParticipate());
            $finishTime = $participation->getFinishTime();

            if ($participateDateTime > $mostRecent) {
                $mostRecent = $participateDateTime;
            }
            if ($finishTime && $finishTime > $mostRecent) {
                $mostRecent = $finishTime;
            }
        }

        return $mostRecent;
    }

    /**
     * Vérifie si l'utilisateur a terminé toutes les énigmes de la chasse.
     */
    private function isHuntCompleted(TreasureHunt $hunt, int $completedCount): bool
    {
        return $hunt->getRiddleCount() === $completedCount;
    }

    /**
     * Calcule le score total d'un utilisateur pour une chasse au trésor donnée.
     *
     * @return array{score: int, time: int, finished: bool, lastParticipate: \DateTimeImmutable}
     */
    public function totalScorePerHunt(TreasureHunt $hunt, User $user): array
    {
        $completedParticipations = $this->getCompletedParticipations($hunt, $user);

        $score = $this->calculateTotalScore($completedParticipations);
        $time = $this->calculateTotalTime($completedParticipations);
        $lastParticipate = $this->findMostRecentParticipationDate($completedParticipations);
        $finished = $this->isHuntCompleted($hunt, count($completedParticipations));

        return [
            'score' => $score,
            'time' => $time,
            'finished' => $finished,
            'lastParticipate' => $lastParticipate,
        ];
    }
}
