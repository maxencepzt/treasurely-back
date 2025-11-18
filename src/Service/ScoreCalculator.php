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

    public function totalScorePerHunt(TreasureHunt $hunt, User $user, \DateTimeImmutable $lastParticipate): array
    {
        $riddles = $hunt->getRiddles();

        $score = 0;
        $time = 0;
        $finished = false;

        $count = 0;

        foreach ($riddles as $riddle) {
            $participations = $riddle->getParticipateRiddles();
            foreach ($participations as $participation) {
                if ($user->getId() == $participation->getHunter()->getId() && $hunt->getId() == $riddle->getHunt()->getId()) {
                    if ($lastParticipate <= $participation->getLastParticipate()) {
                        $lastParticipate = $participation->getLastParticipate();
                    }
                    $score += $participation->getScore();
                    $time += $participation->getFinishTime()->getTimestamp() - $participation->getStartTime()->getTimestamp();
                    if ($lastParticipate <= $participation->getFinishTime()) {
                        $lastParticipate = $participation->getFinishTime();
                    }
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
