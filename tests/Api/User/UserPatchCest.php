<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

final class UserPatchCest
{
    public function canUpdateOwnProfile(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne([
            'nickname' => 'oldnickname',
            'firstname' => 'Old',
            'lastname' => 'Name',
        ])->_real();

        $updatedData = [
            'nickname' => 'newnickname',
            'firstname' => 'New',
            'lastname' => 'Name',
        ];

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPatch('/api/users/'.$user->getId(), $updatedData);

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseIsAnEntity(User::class, '/api/users/'.$user->getId());
        $I->seeResponseContainsJson($updatedData);
    }

    public function cannotUpdateOtherUserProfile(ApiTester $I): void
    {
        // 1. 'Arrange'
        $authenticatedUser = UserFactory::createOne()->_real();
        $otherUser = UserFactory::createOne([
            'nickname' => 'othernickname',
        ])->_real();

        $updatedData = [
            'nickname' => 'hackednickname',
        ];

        // 2. 'Act'
        $I->amLoggedInAs($authenticatedUser);
        $I->sendPatch('/api/users/'.$otherUser->getId(), $updatedData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeResponseIsJson();
    }
}
