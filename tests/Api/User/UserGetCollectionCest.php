<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;

final class UserGetCollectionCest
{
    public function passwordIsNotReturnedInResponse(ApiTester $I): void
    {
        // Le mot de passe ne doit jamais être retourné
        $user = UserFactory::createOne()->_real();

        $I->amLoggedInAs($user);
        $I->sendGet('/api/users/'.$user->getId());

        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeResponseJsonMatchesJsonPath('$.password');
    }

    public function getCollectionDoesNotIncludeDeactivatedUsers(ApiTester $I): void
    {
        // La collection ne retourne que les utilisateurs activés
        UserFactory::createOne(['activated' => true])->_real();
        UserFactory::createOne(['activated' => false])->_real();
        $authenticatedUser = UserFactory::createOne()->_real();

        $I->amLoggedInAs($authenticatedUser);
        $I->sendGet('/api/users');

        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeResponseContainsJson(['activated' => false]);
    }
}
