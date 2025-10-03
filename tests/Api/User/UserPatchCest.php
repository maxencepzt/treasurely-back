<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Entity\User;
use App\Enum\Gender;
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

    public function cannotUpdateProfileAsGuest(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne()->_real();

        $updatedData = [
            'nickname' => 'newnickname',
        ];

        // 2. 'Act'
        $I->sendPatch('/api/users/'.$user->getId(), $updatedData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseIsJson();
    }

    public function canUpdatePasswordAsAuthenticatedUser(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne([
            'password' => 'oldpassword',
        ])->_real();

        $updatedData = [
            'password' => 'newpassword123',
        ];

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPatch('/api/users/'.$user->getId(), $updatedData);

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
    }

    public function canUpdateMultipleFields(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne([
            'nickname' => 'oldnickname',
            'firstname' => 'Old',
            'lastname' => 'Name',
            'email' => 'old@example.com',
            'phone' => '1111111111',
            'public' => false,
            'gender' => Gender::MAN,
        ])->_real();

        $updatedData = [
            'nickname' => 'newnickname',
            'firstname' => 'New',
            'lastname' => 'NewName',
            'email' => 'new@example.com',
            'phone' => '2222222222',
            'public' => true,
            'gender' => Gender::WOMAN->value,
        ];

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPatch('/api/users/'.$user->getId(), $updatedData);

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson($updatedData);
    }

    public function cannotUpdateWithInvalidEmail(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne()->_real();

        $updatedData = [
            'email' => 'invalid-email',
        ];

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPatch('/api/users/'.$user->getId(), $updatedData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function cannotUpdateRolesAsUser(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne([
            'roles' => [],
        ])->_real();

        $updatedData = [
            'roles' => ['ROLE_ADMIN'],
        ];

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPatch('/api/users/'.$user->getId(), $updatedData);

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        // Les rôles ne devraient pas être modifiés
        $I->dontSeeResponseContainsJson(['roles' => ['ROLE_ADMIN']]);
    }

    public function cannotUpdateNonExistentUser(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne()->_real();
        $nonExistentUserId = 99999;

        $updatedData = [
            'nickname' => 'newnickname',
        ];

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPatch('/api/users/'.$nonExistentUserId, $updatedData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
    }
}
