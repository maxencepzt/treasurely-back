<?php

declare(strict_types=1);

namespace App\Tests\Api\Team;

use App\Factory\DesignerTeamFactory;
use App\Factory\PlayerTeamFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

/**
 * `@var` vaut "Team" pour les deux sous-types : `type` est le discriminant du client.
 */
final class TeamGetCest
{
    public function aPlayerTeamAnnouncesItsTypeAndItsJoinCode(ApiTester $I): void
    {
        $team = PlayerTeamFactory::createOne(['owner' => UserFactory::createOne(), 'code' => 'treasurely_42'])->_real();

        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/teams/'.$team->getId());

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['type' => 'player', 'code' => 'treasurely_42']);
    }

    public function aDesignerTeamAnnouncesItsTypeWithoutAnyCode(ApiTester $I): void
    {
        $team = DesignerTeamFactory::createOne(['owner' => UserFactory::createOne()])->_real();

        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/teams/'.$team->getId());

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['type' => 'designer']);
        $I->dontSeeResponseJsonMatchesJsonPath('$.code');
    }

    public function theUserTeamsCarryTheirTypeAndName(ApiTester $I): void
    {
        $user = UserFactory::createOne()->_real();
        $team = PlayerTeamFactory::createOne(['owner' => $user, 'name' => 'Les Fouineurs'])->_real();

        $I->amLoggedInAs($user);
        $I->sendGet('/api/users/'.$user->getId().'/teams');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['teams' => [['@id' => '/api/teams/'.$team->getId(), 'name' => 'Les Fouineurs', 'type' => 'player']]]);
    }
}
