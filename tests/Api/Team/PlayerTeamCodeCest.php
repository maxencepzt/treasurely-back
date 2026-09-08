<?php

declare(strict_types=1);

namespace App\Tests\Api\Team;

use App\Entity\PlayerTeam;
use App\Factory\PlayerTeamFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PlayerTeamCodeCest
{
    public function aPlayerTeamValidatesWithItsOwnCode(ApiTester $I): void
    {
        // 1. 'Arrange'
        $team = PlayerTeamFactory::createOne(['owner' => UserFactory::createOne(), 'code' => 'treasurely_0000000000001'])->_real();

        // 2. 'Act'
        $violations = $I->grabService(ValidatorInterface::class)->validate($team);

        // 3. 'Assert'
        $I->assertCount(0, $violations);
    }

    public function twoPlayerTeamsCannotShareAJoinCode(ApiTester $I): void
    {
        // 1. 'Arrange'
        $owner = UserFactory::createOne()->_real();
        PlayerTeamFactory::createOne(['owner' => $owner, 'code' => 'treasurely_0000000000002']);
        $duplicate = (new PlayerTeam())
            ->setName('Doublon')
            ->setDescription('Même code de jointure')
            ->setOwner($owner)
            ->setCode('treasurely_0000000000002');

        // 2. 'Act'
        $violations = $I->grabService(ValidatorInterface::class)->validate($duplicate);

        // 3. 'Assert'
        $I->assertCount(1, $violations);
        $I->assertSame('code', $violations[0]->getPropertyPath());
    }

    public function aMemberReadsTheJoinCode(ApiTester $I): void
    {
        // 1. 'Arrange' : le propriétaire est membre d'office
        $owner = UserFactory::createOne()->_real();
        $team = PlayerTeamFactory::createOne(['owner' => $owner, 'code' => 'treasurely_0000000000003'])->_real();

        // 2. 'Act'
        $I->amLoggedInAs($owner);
        $I->sendGet('/api/player_teams/'.$team->getId().'/code');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['code' => 'treasurely_0000000000003']);
    }

    public function aStrangerCannotReadTheJoinCode(ApiTester $I): void
    {
        // 1. 'Arrange' : le joueur est créé après l'équipe, la factory ne peut pas l'y avoir mis
        $team = PlayerTeamFactory::createOne(['owner' => UserFactory::createOne(), 'code' => 'treasurely_0000000000004'])->_real();

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/player_teams/'.$team->getId().'/code');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }
}
