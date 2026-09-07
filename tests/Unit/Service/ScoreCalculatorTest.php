<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\DesignerTeam;
use App\Entity\ParticipateRiddle;
use App\Entity\TextRiddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Service\ScoreCalculator;
use Codeception\Test\Unit;

/**
 * Le cœur métier, sans base de données : le barème, l'exclusion des concepteurs et les
 * agrégats d'une participation à une chasse.
 */
final class ScoreCalculatorTest extends Unit
{
    private ScoreCalculator $calculator;

    protected function _before(): void
    {
        $this->calculator = new ScoreCalculator();
    }

    public function testAnInstantAnswerIsWorthTheFullDifficultyValue(): void
    {
        $start = new \DateTimeImmutable('2026-09-07 10:00:00');

        $this->assertSame(1000, $this->calculator->calculateScore($start, $start, 1));
        $this->assertSame(3000, $this->calculator->calculateScore($start, $start, 3));
    }

    public function testTheScoreDecaysExponentiallyWithTime(): void
    {
        $start = new \DateTimeImmutable('2026-09-07 10:00:00');

        // 1000 * exp(-0.001 * 1000) = 367.87, tronqué
        $this->assertSame(367, $this->calculator->calculateScore($start, $start->modify('+1000 seconds'), 1));
        $this->assertSame(1940, $this->calculator->calculateScore($start, $start->modify('+30 seconds'), 2));
        $this->assertSame(0, $this->calculator->calculateScore($start, $start->modify('+1 day'), 3));
    }

    public function testTheOrderOfTheDatesDoesNotMatter(): void
    {
        $start = new \DateTimeImmutable('2026-09-07 10:00:00');
        $end = $start->modify('+100 seconds');

        $this->assertSame(
            $this->calculator->calculateScore($start, $end, 2),
            $this->calculator->calculateScore($end, $start, 2)
        );
    }

    public function testTheOwnerAndTheDesignersCannotPlayTheirHunt(): void
    {
        $owner = new User();
        $designer = new User();
        $stranger = new User();
        $team = (new DesignerTeam())->setOwner($owner)->addMember($owner)->addMember($designer);
        $hunt = (new TreasureHunt())->setOwner($owner)->setDesignerTeam($team);

        $this->assertFalse($this->calculator->canUserPlay($owner, $hunt));
        $this->assertFalse($this->calculator->canUserPlay($designer, $hunt));
        $this->assertTrue($this->calculator->canUserPlay($stranger, $hunt));
    }

    public function testARiddleWithoutHuntCannotBePlayed(): void
    {
        $this->assertFalse($this->calculator->canUserParticipate(new User(), new TextRiddle()));
    }

    public function testTheHuntTotalsOnlyCountTheSolvedRiddlesOfThePlayer(): void
    {
        $player = new User();
        $other = new User();
        $hunt = (new TreasureHunt())->setRiddleCount(2);
        $first = new TextRiddle();
        $second = new TextRiddle();
        $hunt->addRiddle($first)->addRiddle($second);
        $start = new \DateTimeImmutable('2026-09-07 10:00:00');

        $first->addParticipateRiddle($this->participation($player, $start, $start->modify('+40 seconds'), 500));
        $second->addParticipateRiddle($this->participation($player, $start->modify('+1 minute'), null, 0));
        $second->addParticipateRiddle($this->participation($other, $start, $start->modify('+10 seconds'), 900));

        $totals = $this->calculator->totalScorePerHunt($hunt, $player);

        $this->assertSame(500, $totals['score']);
        $this->assertSame(40, $totals['time']);
        $this->assertFalse($totals['finished']);
        $this->assertEquals($start->modify('+40 seconds'), $totals['lastParticipate']);
    }

    public function testTheHuntIsFinishedOnceEveryRiddleIsSolved(): void
    {
        $player = new User();
        $hunt = (new TreasureHunt())->setRiddleCount(2);
        $first = new TextRiddle();
        $second = new TextRiddle();
        $hunt->addRiddle($first)->addRiddle($second);
        $start = new \DateTimeImmutable('2026-09-07 10:00:00');

        $first->addParticipateRiddle($this->participation($player, $start, $start->modify('+40 seconds'), 500));
        $second->addParticipateRiddle($this->participation($player, $start->modify('+1 minute'), $start->modify('+90 seconds'), 700));

        $totals = $this->calculator->totalScorePerHunt($hunt, $player);

        $this->assertSame(1200, $totals['score']);
        $this->assertSame(70, $totals['time']);
        $this->assertTrue($totals['finished']);
        $this->assertEquals($start->modify('+90 seconds'), $totals['lastParticipate']);
    }

    private function participation(User $player, \DateTimeImmutable $start, ?\DateTimeImmutable $finish, int $score): ParticipateRiddle
    {
        return (new ParticipateRiddle())
            ->setHunter($player)
            ->setStartTime($start)
            ->setFinishTime($finish)
            ->setLastParticipate(\DateTime::createFromImmutable($finish ?? $start))
            ->setScore($score);
    }
}
