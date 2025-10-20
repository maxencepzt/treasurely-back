<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Enum\Gender;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

final class UserPostCest
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
            'profilePicture' => 'array',
            'totalTime' => 'integer',
            'totalHunt' => 'integer',
        ];
    }

    public function canRegisterNewUser(ApiTester $I): void
    {
        // 1. 'Arrange'
        $birthDate = new \DateTime('1995-06-15');
        $userData = [
            'nickname' => 'newuser',
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'newuser@example.com',
            'birthDate' => $birthDate->format('Y-m-d'),
            'phone' => '0123456789',
            'password' => 'securepassword123',
            'public' => true,
            'gender' => Gender::MAN->value,
        ];

        // 2. 'Act'
        $I->sendPost('/api/register', $userData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'nickname' => 'newuser',
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'newuser@example.com',
            'public' => true,
            'gender' => Gender::MAN->value,
        ]);
        $I->seeResponseJsonMatchesJsonPath('$.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.password');
    }

    public function cannotRegisterWithDuplicateNickname(ApiTester $I): void
    {
        // 1. 'Arrange'
        UserFactory::createOne([
            'nickname' => 'existinguser',
        ]);

        $userData = [
            'nickname' => 'existinguser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'unique@example.com',
            'birthDate' => '1995-06-15',
            'phone' => '0123456789',
            'password' => 'securepassword123',
            'public' => true,
            'gender' => Gender::MAN->value,
        ];

        // 2. 'Act'
        $I->sendPost('/api/register', $userData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function cannotRegisterWithDuplicateEmail(ApiTester $I): void
    {
        // 1. 'Arrange'
        UserFactory::createOne([
            'email' => 'existing@example.com',
        ]);

        $userData = [
            'nickname' => 'uniquenickname',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'existing@example.com',
            'birthDate' => '1995-06-15',
            'phone' => '0123456789',
            'password' => 'securepassword123',
            'public' => true,
            'gender' => Gender::MAN->value,
        ];

        // 2. 'Act'
        $I->sendPost('/api/register', $userData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function cannotRegisterWithInvalidEmail(ApiTester $I): void
    {
        // 1. 'Arrange'
        $userData = [
            'nickname' => 'newuser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'invalid-email-format',
            'birthDate' => '1995-06-15',
            'phone' => '0123456789',
            'password' => 'securepassword123',
            'public' => true,
            'gender' => Gender::MAN->value,
        ];

        // 2. 'Act'
        $I->sendPost('/api/register', $userData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function cannotRegisterWithInvalidGender(ApiTester $I): void
    {
        // 1. 'Arrange'
        $userData = [
            'nickname' => 'newuser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'birthDate' => '1995-06-15',
            'phone' => '0123456789',
            'password' => 'securepassword123',
            'public' => true,
            'gender' => 'INVALID_GENDER',
        ];

        // 2. 'Act'
        $I->sendPost('/api/register', $userData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }

    public function cannotRegisterWithInvalidBirthDate(ApiTester $I): void
    {
        // 1. 'Arrange'
        $userData = [
            'nickname' => 'newuser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'birthDate' => 'invalid-date-format',
            'phone' => '0123456789',
            'password' => 'securepassword123',
            'public' => true,
            'gender' => Gender::MAN->value,
        ];

        // 2. 'Act'
        $I->sendPost('/api/register', $userData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }

    public function cannotRegisterWithFutureBirthDate(ApiTester $I): void
    {
        // 1. 'Arrange'
        $futureBirthDate = (new \DateTime('+1 year'))->format('Y-m-d');
        $userData = [
            'nickname' => 'newuser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'birthDate' => $futureBirthDate,
            'phone' => '0123456789',
            'password' => 'securepassword123',
            'public' => true,
            'gender' => Gender::MAN->value,
        ];

        // 2. 'Act'
        $I->sendPost('/api/register', $userData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function creationDateIsAutomaticallySet(ApiTester $I): void
    {
        // 1. 'Arrange'
        $userData = [
            'nickname' => 'dateuser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'date@example.com',
            'birthDate' => '1995-06-15',
            'phone' => '0123456789',
            'password' => 'securepassword123',
            'public' => true,
            'gender' => Gender::MAN->value,
        ];

        // 2. 'Act'
        $I->sendPost('/api/register', $userData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.creationDate');
    }
}
