<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

final class UserDeleteCest
{
    public function canDeleteOwnAccount(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne([
            'nickname' => 'deleteme',
            'password' => 'ValidPass123!',
        ]);
        $I->amLoggedInAs($user->_real());

        // 2. 'Act'
        $I->sendDelete('/api/users/'.$user->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->dontSeeInRepository(User::class, ['nickname' => 'deleteme']);
    }

    public function cannotDeleteAccountWithoutAuthentication(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne(['nickname' => 'someuser']);

        // 2. 'Act'
        $I->sendDelete('/api/users/'.$user->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeInRepository(User::class, ['nickname' => 'someuser']);
    }
}
