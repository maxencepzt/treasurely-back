<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;

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
}
