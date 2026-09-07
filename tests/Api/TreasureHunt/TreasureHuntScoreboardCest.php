<?php

declare(strict_types=1);

namespace App\Tests\Api\TreasureHunt;

use App\Entity\ParticipateHunt;
use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\State\ScoreboardProvider;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Examples;
use Codeception\Example;
use Codeception\Util\HttpCode;

/**
 * GET /treasure_hunts/{id}/scoreboard : dix finisseurs à qui se comparer.
 */
final class TreasureHuntScoreboardCest
{
    /**
     * @return array{TreasureHunt, Riddle}
     */
    private function hunt(): array
    {
        DesignerTeamFactory::createOne(['owner' => UserFactory::createOne()]); // la factory de chasse en tire une au hasard
        HuntTypeFactory::createOne();
        $hunt = TreasureHuntFactory::createOne(['status' => TreasureHunt::STATE_OPENED, 'riddleCount' => 1])->_real();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 1])->_real();

        return [$hunt, $riddle];
    }

    private function participation(ApiTester $I, User $player, TreasureHunt $hunt, Riddle $riddle, int $score, int $time, bool $finished = true): ParticipateHunt
    {
        $participation = (new ParticipateHunt())
            ->setHunter($player)
            ->setHunt($hunt)
            ->setCurrentRiddle($riddle)
            ->setFinished($finished)
            ->setScore($score)
            ->setTime($time)
            ->setLastParticipate(new \DateTimeImmutable());
        $I->haveInRepository($participation);

        return $participation;
    }

    /**
     * Finisseurs déjà classés : le premier de la liste a le meilleur score.
     *
     * @return User[]
     */
    private function finishers(ApiTester $I, TreasureHunt $hunt, Riddle $riddle, int $count): array
    {
        $players = [];
        foreach (UserFactory::createMany($count) as $index => $proxy) {
            $player = $proxy->_real();
            $this->participation($I, $player, $hunt, $riddle, 1000 * ($count - $index), 100 * ($index + 1));
            $players[] = $player;
        }

        return $players;
    }

    /**
     * @return int[]
     */
    private function ranks(ApiTester $I): array
    {
        return array_map(intval(...), $I->grabDataFromResponseByJsonPath('$.member[*].rank'));
    }

    public function aNewcomerSeesTheTopTenFinishersOnly(ApiTester $I): void
    {
        [$hunt, $riddle] = $this->hunt();
        $finishers = $this->finishers($I, $hunt, $riddle, 15);
        $quitter = UserFactory::createOne(['nickname' => 'abandon'])->_real();
        $this->participation($I, $quitter, $hunt, $riddle, 99999, 1, finished: false);

        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/treasure_hunts/'.$hunt->getId().'/scoreboard');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertSame(range(1, ScoreboardProvider::SIZE), $this->ranks($I));
        $I->seeResponseContainsJson(['member' => [['rank' => 1, 'score' => 15000, 'hunter' => ['nickname' => $finishers[0]->getNickname()]]]]);
        $I->dontSeeResponseContains('abandon');
    }

    /**
     * Cinq au-dessus, quatre en dessous, et la fenêtre glisse aux deux bouts.
     *
     * @param Example<int, int> $example
     */
    #[Examples(8, 3, 12)]
    #[Examples(2, 1, 10)]
    #[Examples(1, 1, 10)]
    #[Examples(14, 6, 15)]
    #[Examples(15, 6, 15)]
    public function aFinisherSeesTheWindowAroundThem(ApiTester $I, Example $example): void
    {
        [$hunt, $riddle] = $this->hunt();
        $finishers = $this->finishers($I, $hunt, $riddle, 15);
        [$rank, $first, $last] = [$example[0], $example[1], $example[2]];

        $I->amLoggedInAs($finishers[$rank - 1]);
        $I->sendGet('/api/treasure_hunts/'.$hunt->getId().'/scoreboard');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertSame(range($first, $last), $this->ranks($I));
        $I->seeResponseContainsJson(['member' => [['rank' => $rank, 'hunter' => ['@id' => '/api/users/'.$finishers[$rank - 1]->getId()]]]]);
    }

    public function tiesAreBrokenByTheShortestTime(ApiTester $I): void
    {
        [$hunt, $riddle] = $this->hunt();
        $slow = UserFactory::createOne(['nickname' => 'lent'])->_real();
        $fast = UserFactory::createOne(['nickname' => 'rapide'])->_real();
        $this->participation($I, $slow, $hunt, $riddle, 500, 300);
        $this->participation($I, $fast, $hunt, $riddle, 500, 100);

        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/treasure_hunts/'.$hunt->getId().'/scoreboard');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertSame(['rapide', 'lent'], $I->grabDataFromResponseByJsonPath('$.member[*].hunter.nickname'));
        $I->assertSame([1, 2], $this->ranks($I));
    }

    public function fewerThanTenFinishersAreAllShown(ApiTester $I): void
    {
        [$hunt, $riddle] = $this->hunt();
        $finishers = $this->finishers($I, $hunt, $riddle, 3);

        $I->amLoggedInAs($finishers[2]);
        $I->sendGet('/api/treasure_hunts/'.$hunt->getId().'/scoreboard');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertSame([1, 2, 3], $this->ranks($I));
    }

    public function aRowCarriesTheEssentialsAndNothingPrivate(ApiTester $I): void
    {
        [$hunt, $riddle] = $this->hunt();
        $this->finishers($I, $hunt, $riddle, 1);

        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/treasure_hunts/'.$hunt->getId().'/scoreboard');

        $I->seeResponseCodeIs(HttpCode::OK);
        foreach (['rank', 'score', 'time', 'lastParticipate', 'hunter.nickname', "hunter['@id']"] as $path) {
            $I->seeResponseJsonMatchesJsonPath('$.member[0].'.$path);
        }
        foreach (['hunter.email', 'currentRiddle', 'rate', 'riddlesSolved'] as $path) {
            $I->dontSeeResponseJsonMatchesJsonPath('$.member[0].'.$path);
        }
    }

    public function anUnknownHuntIsNotFound(ApiTester $I): void
    {
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/treasure_hunts/999999/scoreboard');

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function anonymousUsersCannotSeeTheScoreboard(ApiTester $I): void
    {
        [$hunt] = $this->hunt();

        $I->sendGet('/api/treasure_hunts/'.$hunt->getId().'/scoreboard');

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }
}
