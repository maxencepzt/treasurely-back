<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Riddle;
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
}
