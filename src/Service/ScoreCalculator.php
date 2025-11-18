<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use App\Entity\User;

class ScoreCalculator
{
    public function calculateScore(\DateTimeImmutable $startTime, \DateTimeImmutable $endTime, int $difficulty): int
    {
        $time = $endTime->getTimestamp() - $startTime->getTimestamp();

        return (int) ($difficulty * (1000 * exp(-0.001 * (int) abs($time))));
    }

    public function canUserParticipate(User $user, Riddle $riddle): bool
    {
        $hunt = $riddle->getHunt();

        // Le propriétaire de la chasse ne peut pas participer
        if ($user->getId() === $hunt->getOwner()->getId()) {
            return false;
        }

        // Les membres de l'équipe ne peuvent pas participer
        $team = $hunt->getTeam();
        if ($team) {
            foreach ($team->getMembers() as $member) {
                if ($user->getId() === $member->getId()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array{0: int, 1: int, 2: bool}
     */
    public function totalScorePerHunt(TreasureHunt $hunt, User $user, \DateTimeImmutable $lastParticipate): array
    {
        $riddles = $hunt->getRiddles();

        $score = 0;
        $time = 0;
        $finished = false;

        $count = 0;

        foreach ($riddles as $riddle) {
            foreach ($riddle->getParticipateRiddles() as $participation) {
                $hunter = $participation->getHunter();
                $finishTime = $participation->getFinishTime();

                if ($user->getId() === $hunter->getId() && $finishTime) {
                    $score += $participation->getScore();
                    $time += $finishTime->getTimestamp() - $participation->getStartTime()->getTimestamp();
                    $lastParticipate = max(
                        $lastParticipate,
                        $participation->getLastParticipate(),
                        $finishTime
                    );
                    ++$count;
                }
            }
        }

        if ($hunt->getRiddleCount() === $count) {
            $finished = true;
        }

        return [
            $score,
            $time,
            $finished,
        ];
    }
}
