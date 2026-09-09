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

/** DELETE /participate_hunts/{id} : quitter une chasse commencée, comme si on ne s'y était jamais inscrit. */
final class ParticipateHuntDeleteCest
{
    /**
     * @return array{User, TreasureHunt, Riddle}
     */
    private function startedHunt(ApiTester $I, bool $finished = false): array
    {
        DesignerTeamFactory::createOne(['owner' => UserFactory::createOne()]); // la factory de chasse en tire une au hasard
        HuntTypeFactory::createOne();
        $hunt = TreasureHuntFactory::createOne(['status' => TreasureHunt::STATE_OPENED, 'riddleCount' => 2])->_real();
        $first = TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 1])->_real();
        TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 2]);
        $player = UserFactory::createOne()->_real();
        $I->haveInRepository((new ParticipateRiddle())
            ->setHunter($player)
            ->setRiddle($first)
            ->setStartTime(new \DateTimeImmutable('-10 minutes'))
            ->setFinishTime(new \DateTimeImmutable('-9 minutes'))
            ->setLastParticipate(new \DateTime('-9 minutes'))
            ->setScore(900)
            ->setAttempts(1));
        $I->haveInRepository((new ParticipateHunt())
            ->setHunter($player)
            ->setHunt($hunt)
            ->setCurrentRiddle($first)
            ->setFinished($finished)
            ->setScore(900)
            ->setTime(60)
            ->setLastParticipate(new \DateTimeImmutable('-9 minutes')));

        return [$player, $hunt, $first];
    }

    private function progressOf(ApiTester $I, User $player, TreasureHunt $hunt): ParticipateHunt
    {
        return $I->grabEntityFromRepository(ParticipateHunt::class, ['hunter' => $player, 'hunt' => $hunt]);
    }

    public function leavingAStartedHuntRemovesTheParticipationAndItsClocks(ApiTester $I): void
    {
        [$player, $hunt, $first] = $this->startedHunt($I);
        $progress = $this->progressOf($I, $player, $hunt);

        $I->amLoggedInAs($player);
        $I->sendDelete('/api/participate_hunts/'.$progress->getId());

        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->dontSeeInRepository(ParticipateHunt::class, ['hunter' => $player, 'hunt' => $hunt]);
        $I->dontSeeInRepository(ParticipateRiddle::class, ['hunter' => $player, 'riddle' => $first]);
    }

    public function aFinishedHuntCannotBeLeft(ApiTester $I): void
    {
        [$player, $hunt] = $this->startedHunt($I, finished: true);
        $progress = $this->progressOf($I, $player, $hunt);

        $I->amLoggedInAs($player);
        $I->sendDelete('/api/participate_hunts/'.$progress->getId());

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeInRepository(ParticipateHunt::class, ['id' => $progress->getId()]);
    }

    public function aStrangerCannotLeaveForSomeoneElse(ApiTester $I): void
    {
        [$player, $hunt] = $this->startedHunt($I);
        $progress = $this->progressOf($I, $player, $hunt);

        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendDelete('/api/participate_hunts/'.$progress->getId());

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeInRepository(ParticipateHunt::class, ['id' => $progress->getId()]);
    }
}
