<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Entity\User;
use App\Enum\Gender;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

final class UserGetCest
{
    /**
     * @return array<string, string>
     */
    protected static function expectedProperties(): array
    {
        return [
            'id' => 'integer',
            'nickname' => 'string',
            'firstname' => 'string',
            'lastname' => 'string',
            'email' => 'string:email',
            'birthDate' => 'string:date',
            'phone' => 'string',
            'creationDate' => 'string:date',
            'public' => 'boolean',
            'gender' => 'string',
            'profilePicture' => 'string|null',
            'totalTime' => 'integer',
            'totalHunt' => 'integer',
            'description' => 'string',
        ];
    }

    public function getUserDetail(ApiTester $I): void
    {
        // 1. 'Arrange'
        $birthDate = new \DateTime('1990-01-01');
        $creationDate = new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC'));

        $user = UserFactory::createOne([
            'nickname' => 'johndoe',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'johndoe@example.com',
            'birthDate' => $birthDate,
            'phone' => '1234567890',
            'creationDate' => $creationDate,
            'public' => true,
            'gender' => Gender::MAN,
            'totalTime' => 3600,
            'totalHunt' => 15,
            'password' => 'test',
        ])->_real();

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendGet('/api/users/'.$user->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseIsAnEntity(User::class, '/api/users/'.$user->getId());

        $expectedData = [
            'nickname' => 'johndoe',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'johndoe@example.com',
            'birthDate' => $birthDate->format(\DateTimeInterface::W3C),
            'phone' => '1234567890',
            'creationDate' => $creationDate->format(\DateTimeInterface::W3C),
            'public' => true,
            'gender' => Gender::MAN->value,
            'totalTime' => 3600,
            'totalHunt' => 15,
        ];

        $I->seeResponseIsAnItem(self::expectedProperties(), $expectedData);
    }

    public function cannotGetNonExistentUser(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne()->_real();
        $nonExistentUserId = 99999;

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendGet('/api/users/'.$nonExistentUserId);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
    }

    public function cannotGetDeactivatedUserAsUser(ApiTester $I): void
    {
        // 1. 'Arrange'
        $authenticatedUser = UserFactory::createOne()->_real();
        $deactivatedUser = UserFactory::createOne([
            'activated' => false,
        ])->_real();

        // 2. 'Act'
        $I->amLoggedInAs($authenticatedUser);
        $I->sendGet('/api/users/'.$deactivatedUser->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeResponseIsJson();
    }

    public function canGetDeactivatedUserAsAdmin(ApiTester $I): void
    {
        // 1. 'Arrange'
        $admin = UserFactory::createOne([
            'roles' => ['ROLE_ADMIN'],
        ])->_real();
        $deactivatedUser = UserFactory::createOne([
            'activated' => false,
        ])->_real();

        // 2. 'Act'
        $I->amLoggedInAs($admin);
        $I->sendGet('/api/users/'.$deactivatedUser->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseIsAnEntity(User::class, '/api/users/'.$deactivatedUser->getId());
    }

    public function cannotGetUserAsGuest(ApiTester $I): void
    {
        // Un utilisateur non authentifié ne peut pas accéder aux détails d'un utilisateur
        $user = UserFactory::createOne()->_real();

        $I->sendGet('/api/users/'.$user->getId());

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }
}
