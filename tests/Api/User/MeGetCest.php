<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Entity\User;
use App\Enum\Gender;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

final class MeGetCest
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
        ];
    }

    public function canGetAuthenticatedUserProfile(ApiTester $I): void
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
        $I->sendGet('/api/me');

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseIsAnEntity(User::class, '/api/me');

        $expectedData = [
            'id' => $user->getId(),
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

    public function cannotAccessMeRouteAsGuest(ApiTester $I): void
    {
        // 1. 'Arrange' pas d'utilisateur authentifié

        // 2. 'Act'
        $I->sendGet('/api/me');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseIsJson();
    }
}
