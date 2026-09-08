<?php

declare(strict_types=1);

namespace App\Tests\Api\Team;

use App\Entity\PlayerTeam;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

/**
 * POST /player_teams : le joueur donne un nom, le serveur fait le reste (propriétaire,
 * premier membre, code de jointure).
 */
final class PlayerTeamCreateCest
{
    public function canCreateAPlayerTeam(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne()->_real();

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPost('/api/player_teams', ['name' => 'Les Fouineurs', 'description' => 'On cherche partout.']);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseContainsJson(['name' => 'Les Fouineurs', 'type' => 'player', 'memberCount' => 1]);
        $I->dontSeeResponseJsonMatchesJsonPath('$.code');
        $team = $I->grabEntityFromRepository(PlayerTeam::class, ['name' => 'Les Fouineurs']);
        $I->assertSame($user->getId(), $team->getOwner()?->getId());
        $I->assertTrue($team->hasMember($user));
        $I->assertMatchesRegularExpression('/^treasurely_\d{13}$/', $team->getCode());
    }

    public function cannotCreateATeamWithoutAName(ApiTester $I): void
    {
        // 1. 'Arrange'
        $I->amLoggedInAs(UserFactory::createOne()->_real());

        // 2. 'Act'
        $I->sendPost('/api/player_teams', ['name' => '', 'description' => 'Sans nom']);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->assertContains('name', $I->grabDataFromResponseByJsonPath('$.violations[*].propertyPath'));
    }
}
