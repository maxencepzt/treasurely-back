<?php

declare(strict_types=1);

namespace App\Tests\Api\Team;

use App\Factory\DesignerTeamFactory;
use App\Factory\PlayerTeamFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

/**
 * GET /player_teams : l'annuaire des équipes de joueurs, cherchable par nom, sans leur code.
 */
final class PlayerTeamDirectoryCest
{
    public function theDirectoryListsPlayerTeamsOnly(ApiTester $I): void
    {
        // 1. 'Arrange'
        $owner = UserFactory::createOne();
        DesignerTeamFactory::createOne(['owner' => $owner, 'name' => 'Atelier des énigmes']);
        PlayerTeamFactory::createOne(['owner' => $owner, 'name' => 'Les Fouineurs']);
        PlayerTeamFactory::createOne(['owner' => $owner, 'name' => 'Chasseurs de nuit']);

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/player_teams');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertEqualsCanonicalizing(['Les Fouineurs', 'Chasseurs de nuit'], $I->grabDataFromResponseByJsonPath('$.member[*].name'));
        $I->seeResponseContainsJson(['member' => [['name' => 'Les Fouineurs', 'type' => 'player']]]);
        $I->seeResponseJsonMatchesJsonPath('$.member[0].memberCount');
        $I->dontSeeResponseJsonMatchesJsonPath('$.member[*].code');
    }

    public function theDirectoryIsSearchableByName(ApiTester $I): void
    {
        // 1. 'Arrange'
        $owner = UserFactory::createOne();
        PlayerTeamFactory::createOne(['owner' => $owner, 'name' => 'Les Fouineurs']);
        PlayerTeamFactory::createOne(['owner' => $owner, 'name' => 'Chasseurs de nuit']);

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/player_teams?name=FOUIN');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertSame(['Les Fouineurs'], $I->grabDataFromResponseByJsonPath('$.member[*].name'));
    }
}
