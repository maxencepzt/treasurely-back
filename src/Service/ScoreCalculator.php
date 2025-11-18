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
        $check = true;

        if ($user->getId() != $hunt->getOwner()->getId()) {
            $team = $hunt->getTeam();
            $members = $team->getMembers();
            foreach ($members as $member) {
                if ($user->getId() != $member->getId() && $check) {
                    $check = true;
                } else {
                    $check = false;
                }
            }
        } else {
            $check = false;
        }

        return $check;
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
                if ($user->getId() == $participation->getHunter()->getId() && $participation->getFinishTime()) {
                    $score += $participation->getScore();
                    $time += $participation->getFinishTime()->getTimestamp() - $participation->getStartTime()->getTimestamp();
                    $lastParticipate = max(
                        $lastParticipate,
                        $participation->getLastParticipate(),
                        $participation->getFinishTime()
                    );
                    ++$count;
                }
            }
        }

        if ($hunt->getRiddleCount() == $count) {
            $finished = true;
        }

        return [
            $score,
            $time,
            $finished,
        ];
    }
}
