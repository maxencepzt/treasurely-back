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
    private static function expectedProperties(): array
    {
        return [
            'id' => 'integer',
            'nickname' => 'string',
            'creationDate' => 'string:date',
            'public' => 'boolean',
            'gender' => 'string',
            'totalTime' => 'integer',
            'totalHunt' => 'integer',
            'totalScore' => 'integer',
            'totalRiddles' => 'integer',
            'description' => 'string',
        ];
    }

    public function getUserDetail(ApiTester $I): void
    {
        // 1. 'Arrange'
        $creationDate = new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC'));

        $user = UserFactory::createOne([
            'nickname' => 'johndoe',
            'creationDate' => $creationDate,
            'public' => true,
            'gender' => Gender::MAN,
            'totalScore' => 250,
            'totalRiddles' => 42,
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
            'creationDate' => $creationDate->format(\DateTimeInterface::W3C),
            'totalScore' => 250,
            'totalRiddles' => 42,
            'public' => true,
            'gender' => Gender::MAN->value,
            'totalTime' => 3600,
            'totalHunt' => 15,
        ];

        $I->seeResponseIsAnItem(self::expectedProperties(), $expectedData);

        // Les champs sensibles ne doivent pas être retournés dans user:read
        $I->dontSeeResponseJsonMatchesJsonPath('$.firstname');
        $I->dontSeeResponseJsonMatchesJsonPath('$.lastname');
        $I->dontSeeResponseJsonMatchesJsonPath('$.email');
        $I->dontSeeResponseJsonMatchesJsonPath('$.birthDate');
        $I->dontSeeResponseJsonMatchesJsonPath('$.phone');
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
