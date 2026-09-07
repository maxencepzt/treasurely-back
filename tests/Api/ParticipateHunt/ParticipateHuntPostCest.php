<?php

declare(strict_types=1);

namespace App\Tests\Api\ParticipateHunt;

use App\Entity\ParticipateHunt;
use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\PlayerTeamFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Examples;
use Codeception\Example;
use Codeception\Util\HttpCode;

/**
 * POST /participate_hunts : rejoindre une chasse. Le serveur fixe le joueur et la première
 * énigme ; le client ne choisit que la chasse et, s'il le veut, une de ses équipes.
 */
final class ParticipateHuntPostCest
{
    /**
     * Les équipes sont créées avant les joueurs : leurs factories recrutent des membres
     * parmi les utilisateurs existants. La seconde énigme est créée avant la première pour
     * prouver que le départ suit l'ordre du parcours, pas celui de la création.
     *
     * @return array{User, TreasureHunt, ?Riddle}
     */
    private function hunt(string $status = TreasureHunt::STATE_OPENED, bool $withRiddles = true): array
    {
        $owner = UserFactory::createOne()->_real();
        $team = DesignerTeamFactory::createOne(['owner' => $owner])->_real();
        HuntTypeFactory::createOne();
        $hunt = TreasureHuntFactory::createOne(['owner' => $owner, 'designerTeam' => $team, 'status' => $status, 'riddleCount' => 2])->_real();
        $first = null;
        if ($withRiddles) {
            TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 2]);
            $first = TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 1])->_real();
        }

        return [$owner, $hunt, $first];
    }

    private function iri(TreasureHunt $hunt): string
    {
        return '/api/treasure_hunts/'.$hunt->getId();
    }

    public function joiningAnOpenedHuntStartsAtItsFirstRiddle(ApiTester $I): void
    {
        [, $hunt, $first] = $this->hunt();
        $player = UserFactory::createOne()->_real();

        $I->amLoggedInAs($player);
        $I->sendPost('/api/participate_hunts', ['hunt' => $this->iri($hunt)]);

        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseContainsJson([
            'hunter' => '/api/users/'.$player->getId(),
            'hunt' => $this->iri($hunt),
            'currentRiddle' => '/api/riddles/'.$first->getId(),
            'riddlesSolved' => 0,
            'finished' => false,
            'score' => 0,
        ]);
        $I->dontSeeResponseJsonMatchesJsonPath('$.playerTeam');
        $I->seeInRepository(ParticipateHunt::class, ['hunter' => $player, 'hunt' => $hunt]);
    }

    public function theHunterInTheBodyIsIgnored(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();
        $other = UserFactory::createOne()->_real();
        $player = UserFactory::createOne()->_real();

        $I->amLoggedInAs($player);
        $I->sendPost('/api/participate_hunts', ['hunt' => $this->iri($hunt), 'hunter' => '/api/users/'.$other->getId(), 'score' => 9999]);

        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseContainsJson(['hunter' => '/api/users/'.$player->getId(), 'score' => 0]);
        $I->dontSeeInRepository(ParticipateHunt::class, ['hunter' => $other]);
    }

    public function joiningTwiceIsRefused(ApiTester $I): void
    {
        [, $hunt, $first] = $this->hunt();
        $player = UserFactory::createOne()->_real();
        $I->haveInRepository((new ParticipateHunt())->setHunter($player)->setHunt($hunt)->setCurrentRiddle($first)->setLastParticipate(new \DateTimeImmutable()));

        $I->amLoggedInAs($player);
        $I->sendPost('/api/participate_hunts', ['hunt' => $this->iri($hunt)]);

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->assertSame(1, $I->grabNumRecords(ParticipateHunt::class, ['hunter' => $player]));
    }

    /**
     * @param Example<int, string> $example
     */
    #[Examples(TreasureHunt::STATE_DRAFT)]
    #[Examples(TreasureHunt::STATE_CLOSED)]
    public function aHuntThatIsNotOpenedCannotBeJoined(ApiTester $I, Example $example): void
    {
        [, $hunt] = $this->hunt($example[0]);
        $player = UserFactory::createOne()->_real();

        $I->amLoggedInAs($player);
        $I->sendPost('/api/participate_hunts', ['hunt' => $this->iri($hunt)]);

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->dontSeeInRepository(ParticipateHunt::class, ['hunter' => $player]);
    }

    public function theDesignerCannotJoinTheirOwnHunt(ApiTester $I): void
    {
        [$owner, $hunt] = $this->hunt();

        $I->amLoggedInAs($owner);
        $I->sendPost('/api/participate_hunts', ['hunt' => $this->iri($hunt)]);

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function aHuntWithoutRiddlesCannotBeJoined(ApiTester $I): void
    {
        [, $hunt] = $this->hunt(withRiddles: false);
        $player = UserFactory::createOne()->_real();

        $I->amLoggedInAs($player);
        $I->sendPost('/api/participate_hunts', ['hunt' => $this->iri($hunt)]);

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
    }

    public function joiningForAForeignTeamIsRefused(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();
        $team = PlayerTeamFactory::createOne()->_real();
        $player = UserFactory::createOne()->_real();
        $I->assertFalse($team->hasMember($player));

        $I->amLoggedInAs($player);
        $I->sendPost('/api/participate_hunts', ['hunt' => $this->iri($hunt), 'playerTeam' => '/api/teams/'.$team->getId()]);

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->dontSeeInRepository(ParticipateHunt::class, ['hunter' => $player]);
    }

    public function joiningForOneOfMyTeamsIsRecorded(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();
        $player = UserFactory::createOne()->_real();
        $team = PlayerTeamFactory::createOne(['owner' => $player])->_real();
        $I->assertTrue($team->hasMember($player));

        $I->amLoggedInAs($player);
        $I->sendPost('/api/participate_hunts', ['hunt' => $this->iri($hunt), 'playerTeam' => '/api/teams/'.$team->getId()]);

        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseContainsJson(['playerTeam' => '/api/teams/'.$team->getId()]);
    }

    public function theHuntIsRequired(ApiTester $I): void
    {
        $player = UserFactory::createOne()->_real();

        $I->amLoggedInAs($player);
        $I->sendPost('/api/participate_hunts', []);

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    public function anonymousUsersCannotJoin(ApiTester $I): void
    {
        [, $hunt] = $this->hunt();

        $I->sendPost('/api/participate_hunts', ['hunt' => $this->iri($hunt)]);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }
}
