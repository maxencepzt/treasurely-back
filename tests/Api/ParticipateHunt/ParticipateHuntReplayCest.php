<?php

declare(strict_types=1);

namespace App\Tests\Api\ParticipateHunt;

use App\Entity\ParticipateHunt;
use App\Entity\ParticipateRiddle;
use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

/**
 * POST /participate_hunts/{id}/replay : refaire une chasse terminée. Le nouveau score
 * remplacera l'ancien, qui n'est pas conservé.
 */
final class ParticipateHuntReplayCest
{
    /**
     * @return array{User, TreasureHunt, Riddle, Riddle}
     */
    private function playedHunt(ApiTester $I, string $status = TreasureHunt::STATE_OPENED, bool $finished = true): array
    {
        DesignerTeamFactory::createOne(['owner' => UserFactory::createOne()]); // la factory de chasse en tire une au hasard
        HuntTypeFactory::createOne();
        $hunt = TreasureHuntFactory::createOne(['status' => $status, 'riddleCount' => 2])->_real();
        $second = TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 2])->_real();
        $first = TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 1])->_real();
        $player = UserFactory::createOne()->_real();
        foreach ([$first, $second] as $riddle) {
            $I->haveInRepository((new ParticipateRiddle())
                ->setHunter($player)
                ->setRiddle($riddle)
                ->setStartTime(new \DateTimeImmutable('-10 minutes'))
                ->setFinishTime($finished || $riddle === $first ? new \DateTimeImmutable('-9 minutes') : null)
                ->setLastParticipate(new \DateTime('-9 minutes'))
                ->setScore(3500)
                ->setAttempts(1));
        }
        $I->haveInRepository((new ParticipateHunt())
            ->setHunter($player)
            ->setHunt($hunt)
            ->setCurrentRiddle($second)
            ->setFinished($finished)
            ->setScore(7000)
            ->setTime(120)
            ->setRate(4)
            ->setLastParticipate(new \DateTimeImmutable('-9 minutes')));

        return [$player, $hunt, $first, $second];
    }

    private function progressOf(ApiTester $I, User $player, TreasureHunt $hunt): ParticipateHunt
    {
        return $I->grabEntityFromRepository(ParticipateHunt::class, ['hunter' => $player, 'hunt' => $hunt]);
    }

    public function replayingAFinishedHuntStartsItOverWithoutTheOldScore(ApiTester $I): void
    {
        [$player, $hunt, $first] = $this->playedHunt($I);
        $progress = $this->progressOf($I, $player, $hunt);

        $I->amLoggedInAs($player);
        $I->sendPost('/api/participate_hunts/'.$progress->getId().'/replay', []);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson([
            'id' => $progress->getId(),
            'finished' => false,
            'score' => 0,
            'time' => 0,
            'riddlesSolved' => 0,
            'rate' => 4,
            'currentRiddle' => '/api/riddles/'.$first->getId(),
        ]);
        $I->assertEqualsWithDelta(time(), $progress->getLastParticipate()->getTimestamp(), 5);
        // Les chronomètres des énigmes sont effacés : la prochaine lecture repart de zéro
        $I->assertSame(0, $I->grabNumRecords(ParticipateRiddle::class, ['hunter' => $player]));
        // Les statistiques du joueur suivent, par les écouteurs Doctrine
        $I->assertSame(0, $player->getTotalScore());
        $I->assertSame(0, $player->getTotalRiddles());
    }

    public function aHuntStillInProgressCannotBeReplayed(ApiTester $I): void
    {
        [$player, $hunt] = $this->playedHunt($I, finished: false);
        $progress = $this->progressOf($I, $player, $hunt);

        $I->amLoggedInAs($player);
        $I->sendPost('/api/participate_hunts/'.$progress->getId().'/replay', []);

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->assertSame(2, $I->grabNumRecords(ParticipateRiddle::class, ['hunter' => $player]));
    }

    public function aClosedHuntCannotBeReplayed(ApiTester $I): void
    {
        [$player, $hunt] = $this->playedHunt($I, TreasureHunt::STATE_CLOSED);
        $progress = $this->progressOf($I, $player, $hunt);

        $I->amLoggedInAs($player);
        $I->sendPost('/api/participate_hunts/'.$progress->getId().'/replay', []);

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->assertTrue($progress->isFinished());
        $I->assertSame(7000, $progress->getScore());
    }

    public function anotherPlayersParticipationCannotBeReplayed(ApiTester $I): void
    {
        [$player, $hunt] = $this->playedHunt($I);
        $progress = $this->progressOf($I, $player, $hunt);

        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendPost('/api/participate_hunts/'.$progress->getId().'/replay', []);

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->assertTrue($progress->isFinished());
    }

    public function anUnknownParticipationIsNotFound(ApiTester $I): void
    {
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendPost('/api/participate_hunts/999999/replay', []);

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function anonymousUsersCannotReplay(ApiTester $I): void
    {
        [$player, $hunt] = $this->playedHunt($I);
        $progress = $this->progressOf($I, $player, $hunt);

        $I->sendPost('/api/participate_hunts/'.$progress->getId().'/replay', []);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }
}
