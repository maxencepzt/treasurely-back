<?php

declare(strict_types=1);

namespace App\Tests\Api\Team;

use App\Entity\PlayerTeam;
use App\Factory\PlayerTeamFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * POST /player_teams/join : le code vaut invitation, l'entrée est immédiate.
 */
final class PlayerTeamJoinCest
{
    public function canJoinATeamWithItsCode(ApiTester $I): void
    {
        // 1. 'Arrange' : le joueur est créé après l'équipe, la factory ne peut pas l'y avoir mis
        $team = PlayerTeamFactory::createOne(['owner' => UserFactory::createOne(), 'code' => 'treasurely_0000000000042'])->_real();
        $user = UserFactory::createOne()->_real();

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPost('/api/player_teams/join', ['code' => ' treasurely_0000000000042 ']);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['@id' => '/api/teams/'.$team->getId(), 'type' => 'player']);
        $I->grabService(EntityManagerInterface::class)->clear();
        $fresh = $I->grabEntityFromRepository(PlayerTeam::class, ['id' => $team->getId()]);
        $I->assertTrue($fresh->getMembers()->exists(fn (int $key, object $member) => $member->getId() === $user->getId()));
    }

    public function anUnknownCodeIsNotFound(ApiTester $I): void
    {
        // 1. 'Arrange'
        $I->amLoggedInAs(UserFactory::createOne()->_real());

        // 2. 'Act'
        $I->sendPost('/api/player_teams/join', ['code' => 'treasurely_9999999999999']);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseContainsJson(['detail' => 'Aucune équipe ne porte ce code.']);
    }

    public function aMemberCannotJoinTwice(ApiTester $I): void
    {
        // 1. 'Arrange' : le propriétaire est membre d'office
        $owner = UserFactory::createOne()->_real();
        PlayerTeamFactory::createOne(['owner' => $owner, 'code' => 'treasurely_0000000000043']);

        // 2. 'Act'
        $I->amLoggedInAs($owner);
        $I->sendPost('/api/player_teams/join', ['code' => 'treasurely_0000000000043']);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeResponseContainsJson(['detail' => 'Vous êtes déjà membre de cette équipe.']);
    }
}
