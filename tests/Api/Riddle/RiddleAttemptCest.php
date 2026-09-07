<?php

declare(strict_types=1);

namespace App\Tests\Api\Riddle;

use App\Entity\GPSRiddle;
use App\Entity\ParticipateRiddle;
use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Factory\DesignerTeamFactory;
use App\Factory\GPSRiddleFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\MCQRiddleFactory;
use App\Factory\QRRiddleFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Examples;
use Codeception\Example;
use Codeception\Util\HttpCode;

/**
 * Lire une énigme démarre son chronomètre ; y répondre passe par POST /riddles/{id}/attempt.
 * Le serveur arbitre, note, et ne divulgue jamais la solution.
 */
final class RiddleAttemptCest
{
    /**
     * L'équipe est créée avant les joueurs : sa factory recrute ses membres parmi les
     * utilisateurs existants, et un joueur recruté serait exclu du jeu.
     *
     * @return array{User, TreasureHunt}
     */
    private function hunt(string $status = TreasureHunt::STATE_OPENED): array
    {
        $owner = UserFactory::createOne()->_real();
        $team = DesignerTeamFactory::createOne(['owner' => $owner])->_real();
        HuntTypeFactory::createOne();
        $hunt = TreasureHuntFactory::createOne(['owner' => $owner, 'designerTeam' => $team, 'status' => $status])->_real();

        return [$owner, $hunt];
    }

    /**
     * Équivalent d'une lecture de l'énigme il y a trente secondes.
     */
    private function start(ApiTester $I, User $player, Riddle $riddle, int $attempts = 0, bool $solved = false): ParticipateRiddle
    {
        $participation = (new ParticipateRiddle())
            ->setHunter($player)
            ->setRiddle($riddle)
            ->setStartTime(new \DateTimeImmutable('-30 seconds'))
            ->setFinishTime($solved ? new \DateTimeImmutable() : null)
            ->setLastParticipate(new \DateTime())
            ->setAttempts($attempts);
        $I->haveInRepository($participation);

        return $participation;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function attempt(ApiTester $I, User $player, Riddle $riddle, array $body): void
    {
        $I->amLoggedInAs($player);
        $I->sendPost('/api/riddles/'.$riddle->getId().'/attempt', $body);
    }

    public function readingARiddleStartsTheClock(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt])->_real();
        $player = UserFactory::createOne()->_real();

        $I->amLoggedInAs($player);
        $I->sendGet('/api/riddles/'.$riddle->getId());

        $I->seeResponseCodeIs(HttpCode::OK);
        $participation = $I->grabEntityFromRepository(ParticipateRiddle::class, ['hunter' => $player, 'riddle' => $riddle]);
        $I->assertNull($participation->getFinishTime());
        $I->assertSame(0, $participation->getAttempts());
        $I->assertEqualsWithDelta(time(), $participation->getStartTime()->getTimestamp(), 5);
    }

    public function readingItAgainKeepsTheFirstClock(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt])->_real();
        $player = UserFactory::createOne()->_real();
        $first = $this->start($I, $player, $riddle);

        $I->amLoggedInAs($player);
        $I->sendGet('/api/riddles/'.$riddle->getId());

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertSame(1, $I->grabNumRecords(ParticipateRiddle::class, ['hunter' => $player, 'riddle' => $riddle]));
        $I->assertEqualsWithDelta(time() - 30, $first->getStartTime()->getTimestamp(), 5);
    }

    /**
     * @param Example<int, string> $example
     */
    #[Examples('owner', TreasureHunt::STATE_OPENED)]
    #[Examples('player', TreasureHunt::STATE_CLOSED)]
    #[Examples('player', TreasureHunt::STATE_DRAFT)]
    public function noClockStartsForTheDesignerOrOutsideAnOpenedHunt(ApiTester $I, Example $example): void
    {
        [$owner, $hunt] = $this->hunt($example[1]);
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt])->_real();
        $reader = 'owner' === $example[0] ? $owner : UserFactory::createOne()->_real();

        $I->amLoggedInAs($reader);
        $I->sendGet('/api/riddles/'.$riddle->getId());

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertSame(0, $I->grabNumRecords(ParticipateRiddle::class, ['riddle' => $riddle]));
    }

    public function aCorrectTextAnswerForgivesCaseAccentsAndSpaces(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt, 'answer' => 'Cathédrale de Reims', 'difficulty' => 2])->_real();
        $player = UserFactory::createOne()->_real();
        $participation = $this->start($I, $player, $riddle);

        $this->attempt($I, $player, $riddle, ['proposal' => '  cathedrale   DE reims ']);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsAnEntity(ParticipateRiddle::class, '/api/participate_riddles/'.$participation->getId());
        $I->seeResponseContainsJson(['solved' => true, 'attempts' => 1, 'attemptsRemaining' => Riddle::DEFAULT_MAX_SCORING_ATTEMPTS - 1]);
        $I->dontSeeResponseJsonMatchesJsonPath('$.answer');
        $score = $I->grabDataFromResponseByJsonPath('$.score')[0];
        // 2 * 1000 * exp(-0.001 * 30) ≈ 1940 : le temps est celui du serveur, pas du client
        $I->assertEqualsWithDelta(1940, $score, 15);
        $I->assertNotNull($participation->getFinishTime());
    }

    public function aWrongAnswerCountsAnAttemptAndKeepsTheSolutionSecret(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt, 'answer' => 'cathédrale'])->_real();
        $player = UserFactory::createOne()->_real();
        $participation = $this->start($I, $player, $riddle);

        $this->attempt($I, $player, $riddle, ['proposal' => 'basilique']);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['solved' => false, 'attempts' => 1, 'attemptsRemaining' => Riddle::DEFAULT_MAX_SCORING_ATTEMPTS - 1, 'score' => 0]);
        $I->dontSeeResponseJsonMatchesJsonPath('$.answer');
        $I->dontSeeResponseContains('cath');
        $I->assertNull($participation->getFinishTime());
    }

    public function aSuccessBeyondTheScoringAttemptsIsAcceptedButWorthless(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt, 'answer' => 'cathédrale'])->_real();
        $player = UserFactory::createOne()->_real();
        $this->start($I, $player, $riddle, attempts: Riddle::DEFAULT_MAX_SCORING_ATTEMPTS);

        $this->attempt($I, $player, $riddle, ['proposal' => 'cathédrale']);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['solved' => true, 'score' => 0, 'attempts' => Riddle::DEFAULT_MAX_SCORING_ATTEMPTS + 1, 'attemptsRemaining' => 0]);
    }

    /**
     * @param Example<int, mixed> $example
     */
    #[Examples(['a', 'c'], true)]
    #[Examples(['c', 'a'], true)]
    #[Examples(['a', 'a', 'c'], true)]
    #[Examples(['a'], false)]
    #[Examples(['a', 'b', 'c'], false)]
    #[Examples([], false)]
    public function anMcqNeedsTheExactSetOfAnswers(ApiTester $I, Example $example): void
    {
        [, $hunt] = $this->hunt();
        $riddle = MCQRiddleFactory::createOne(['hunt' => $hunt, 'choices' => ['a', 'b', 'c', 'd'], 'answers' => ['a', 'c']])->_real();
        $player = UserFactory::createOne()->_real();
        $this->start($I, $player, $riddle);

        $this->attempt($I, $player, $riddle, ['choices' => $example[0]]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['solved' => $example[1]]);
        $I->dontSeeResponseJsonMatchesJsonPath('$.answers');
    }

    /**
     * @param Example<int, mixed> $example
     */
    #[Examples('ABC-123', true)]
    #[Examples(' ABC-123 ', true)]
    #[Examples('abc-123', false)]
    public function aQrCodeMustMatchExactly(ApiTester $I, Example $example): void
    {
        [, $hunt] = $this->hunt();
        $riddle = QRRiddleFactory::createOne(['hunt' => $hunt, 'code' => 'ABC-123'])->_real();
        $player = UserFactory::createOne()->_real();
        $this->start($I, $player, $riddle);

        $this->attempt($I, $player, $riddle, ['proposal' => $example[0]]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['solved' => $example[1]]);
        $I->dontSeeResponseJsonMatchesJsonPath('$.code');
    }

    /**
     * Un degré de latitude vaut environ 111 km : 0,00027° font 30 m, 0,0009° font 100 m.
     *
     * @param Example<int, mixed> $example
     */
    #[Examples(0.0, true)]
    #[Examples(0.00027, true)]
    #[Examples(0.0009, false)]
    public function aGpsPositionCountsWithinTheTolerance(ApiTester $I, Example $example): void
    {
        [, $hunt] = $this->hunt();
        $riddle = GPSRiddleFactory::createOne(['hunt' => $hunt, 'latitude' => 49.2535, 'longitude' => 4.0338])->_real();
        $player = UserFactory::createOne()->_real();
        $this->start($I, $player, $riddle);
        $I->assertSame(50, GPSRiddle::TOLERANCE_METERS);

        $this->attempt($I, $player, $riddle, ['latitude' => 49.2535 + $example[0], 'longitude' => 4.0338]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['solved' => $example[1]]);
        $I->dontSeeResponseJsonMatchesJsonPath('$.latitude');
    }

    public function theDesignerCannotPlayTheirOwnHunt(ApiTester $I): void
    {
        [$owner, $hunt] = $this->hunt();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt, 'answer' => 'cathédrale'])->_real();

        $this->attempt($I, $owner, $riddle, ['proposal' => 'cathédrale']);

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->assertSame(0, $I->grabNumRecords(ParticipateRiddle::class, ['riddle' => $riddle]));
    }

    public function anUnreadRiddleCannotBeAnswered(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt, 'answer' => 'cathédrale'])->_real();
        $player = UserFactory::createOne()->_real();

        $this->attempt($I, $player, $riddle, ['proposal' => 'cathédrale']);

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->assertSame(0, $I->grabNumRecords(ParticipateRiddle::class, ['riddle' => $riddle]));
    }

    public function aSolvedRiddleIsNotAnsweredTwice(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt, 'answer' => 'cathédrale'])->_real();
        $player = UserFactory::createOne()->_real();
        $participation = $this->start($I, $player, $riddle, attempts: 1, solved: true);

        $this->attempt($I, $player, $riddle, ['proposal' => 'cathédrale']);

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->assertSame(1, $participation->getAttempts());
    }

    public function aClosedHuntRefusesAnswers(ApiTester $I): void
    {
        [, $hunt] = $this->hunt(TreasureHunt::STATE_CLOSED);
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt, 'answer' => 'cathédrale'])->_real();
        $player = UserFactory::createOne()->_real();
        $participation = $this->start($I, $player, $riddle);

        $this->attempt($I, $player, $riddle, ['proposal' => 'cathédrale']);

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->assertNull($participation->getFinishTime());
    }

    /**
     * Un corps mal typé est refusé par API Platform en 400, un corps bien typé mais
     * inadapté au type de l'énigme par le serveur en 422 ; aucun ne compte comme essai.
     *
     * @param Example<int, mixed> $example
     */
    #[Examples(['choices' => ['a']], HttpCode::UNPROCESSABLE_ENTITY)]
    #[Examples(['proposal' => null], HttpCode::UNPROCESSABLE_ENTITY)]
    #[Examples(['proposal' => 'x', 'latitude' => 'nord'], HttpCode::BAD_REQUEST)]
    public function aMalformedProposalIsRejectedWithoutConsumingAnAttempt(ApiTester $I, Example $example): void
    {
        [, $hunt] = $this->hunt();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt, 'answer' => 'cathédrale'])->_real();
        $player = UserFactory::createOne()->_real();
        $participation = $this->start($I, $player, $riddle);

        $this->attempt($I, $player, $riddle, (array) $example[0]);

        $I->seeResponseCodeIs($example[1]);
        $I->assertSame(0, $participation->getAttempts());
    }

    public function anOversizedProposalIsRejected(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt])->_real();
        $player = UserFactory::createOne()->_real();
        $this->start($I, $player, $riddle);

        $this->attempt($I, $player, $riddle, ['proposal' => str_repeat('a', 101)]);

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    public function anUnknownRiddleIsNotFound(ApiTester $I): void
    {
        $player = UserFactory::createOne()->_real();

        $I->amLoggedInAs($player);
        $I->sendPost('/api/riddles/999999/attempt', ['proposal' => 'x']);

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function anonymousUsersCannotAnswer(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt])->_real();

        $I->sendPost('/api/riddles/'.$riddle->getId().'/attempt', ['proposal' => 'x']);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }
}
