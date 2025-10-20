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

    public function cannotDeleteAnotherUsersAccount(ApiTester $I): void
    {
        // 1. 'Arrange'
        $authenticatedUser = UserFactory::createOne([
            'nickname' => 'authenticateduser',
            'password' => 'ValidPass123!',
        ]);
        $targetUser = UserFactory::createOne(['nickname' => 'targetuser']);
        $I->amLoggedInAs($authenticatedUser->_real());

        // 2. 'Act'
        $I->sendDelete('/api/users/'.$targetUser->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeInRepository(User::class, ['nickname' => 'targetuser']);
    }

    public function cannotDeleteNonExistentUser(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne([
            'nickname' => 'authenticateduser',
            'password' => 'ValidPass123!',
        ]);
        $I->amLoggedInAs($user->_real());
        $nonExistentId = 99999;

        // 2. 'Act'
        $I->sendDelete('/api/users/'.$nonExistentId);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deletingAccountRemovesRelatedData(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne([
            'nickname' => 'hello',
            'password' => 'ValidPass123!',
        ]);
        $I->amLoggedInAs($user->_real());

        // 2. 'Act'
        $I->sendDelete('/api/users/'.$user->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->dontSeeInRepository(User::class, ['nickname' => 'hello']);
    }

    public function cannotDeleteAccountWithInvalidIdFormat(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne([
            'nickname' => 'authenticateduser',
            'password' => 'ValidPass123!',
        ]);
        $I->amLoggedInAs($user->_real());

        // 2. 'Act'
        $I->sendDelete('/api/users/invalid-id');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }
}
