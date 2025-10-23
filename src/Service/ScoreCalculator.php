<?php

declare(strict_types=1);

namespace App\Service;

class ScoreCalculator
{
    public function calculateScore(\DateTimeImmutable $startTime, \DateTimeImmutable $endTime, int $difficulty): int
    {
        $time = $endTime->getTimestamp() - $startTime->getTimestamp();

        return (int) ($difficulty * (1000 * exp(-0.001 * (int) abs($time))));
    }
}
