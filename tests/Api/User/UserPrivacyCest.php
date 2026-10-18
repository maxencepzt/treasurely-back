<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Factory\PlayerTeamFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

/**
 * Un profil privé ne montre aux autres joueurs que son pseudo et sa photo : le front affiche
 * « Ce profil est privé », l'API ne doit pas en dire plus.
 */
final class UserPrivacyCest
{
    public function aPrivateProfileShowsOnlyItsNicknameToOthers(ApiTester $I): void
    {
        // 1. 'Arrange'
        $private = UserFactory::createOne(['nickname' => 'discret', 'public' => false, 'totalScore' => 250, 'description' => 'Rien à voir'])->_real();

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/users/'.$private->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['nickname' => 'discret', 'public' => false]);
        foreach (['gender', 'description', 'creationDate', 'totalScore', 'totalTime', 'totalHunt', 'totalRiddles'] as $field) {
            $I->dontSeeResponseJsonMatchesJsonPath('$.'.$field);
        }
    }

    public function anAdministratorStillReadsAPrivateProfile(ApiTester $I): void
    {
        // 1. 'Arrange'
        $private = UserFactory::createOne(['public' => false, 'totalScore' => 250])->_real();

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne(['roles' => ['ROLE_ADMIN']])->_real());
        $I->sendGet('/api/users/'.$private->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['totalScore' => 250]);
    }

    public function theParticipationsOfAnotherPlayerAreNotReadable(ApiTester $I): void
    {
        // 1. 'Arrange'
        $other = UserFactory::createOne(['public' => true])->_real();

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/users/'.$other->getId().'/participate_hunts');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function theTeamsOfAPrivateProfileAreNotReadable(ApiTester $I): void
    {
        // 1. 'Arrange'
        $private = UserFactory::createOne(['public' => false])->_real();

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/users/'.$private->getId().'/teams');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function theTeamsOfAPublicProfileAreReadable(ApiTester $I): void
    {
        // 1. 'Arrange'
        $public = UserFactory::createOne(['public' => true])->_real();
        PlayerTeamFactory::createOne(['owner' => $public]);

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/users/'.$public->getId().'/teams');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /** Régression : /api/users/{id}/stats, un outil de débogage, répondait sans authentification. */
    public function theStatsDebugRouteIsGone(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne()->_real();

        // 2. 'Act'
        $I->sendGet('/api/users/'.$user->getId().'/stats');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }
}
