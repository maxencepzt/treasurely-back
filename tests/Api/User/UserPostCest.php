<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Entity\User;
use App\Enum\Gender;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Examples;
use Codeception\Example;
use Codeception\Util\HttpCode;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserPostCest
{
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
            'plainPassword' => 'securepassword123',
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
            'public' => true,
            'gender' => Gender::MAN->value,
        ]);
        $I->seeResponseJsonMatchesJsonPath('$.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.password');
        $I->dontSeeResponseJsonMatchesJsonPath('$.plainPassword');
        // Les champs sensibles ne sont pas retournés après l'enregistrement (user:read seulement)
        $I->dontSeeResponseJsonMatchesJsonPath('$.firstname');
        $I->dontSeeResponseJsonMatchesJsonPath('$.lastname');
        $I->dontSeeResponseJsonMatchesJsonPath('$.email');
        $I->dontSeeResponseJsonMatchesJsonPath('$.birthDate');
        $I->dontSeeResponseJsonMatchesJsonPath('$.phone');
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
            'plainPassword' => 'securepassword123',
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
            'plainPassword' => 'securepassword123',
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
            'plainPassword' => 'securepassword123',
            'public' => true,
            'gender' => Gender::MAN->value,
        ];

        // 2. 'Act'
        $I->sendPost('/api/register', $userData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['violations' => [['propertyPath' => 'email', 'message' => 'Cette adresse email n\'est pas valide.']]]);
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
            'plainPassword' => 'securepassword123',
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
            'plainPassword' => 'securepassword123',
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
            'plainPassword' => 'securepassword123',
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
            'plainPassword' => 'securepassword123',
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

    public function defaultRoleIsUserOnRegistration(ApiTester $I): void
    {
        // 1. 'Arrange'
        $userData = [
            'nickname' => 'roleuser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'role@example.com',
            'birthDate' => '1995-06-15',
            'phone' => '0123456789',
            'plainPassword' => 'securepassword123',
            'public' => true,
            'gender' => Gender::MAN->value,
        ];

        // 2. 'Act'
        $I->sendPost('/api/register', $userData);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        // fetch the user from the test database and assert roles contain ROLE_USER
        $user = $I->grabEntityFromRepository(User::class, ['nickname' => 'roleuser']);
        $I->assertContains('ROLE_USER', $user->getRoles(), 'Default role must include ROLE_USER.');
    }

    public function theRegisteredPasswordIsHashed(ApiTester $I): void
    {
        // 1. 'Arrange' + 2. 'Act'
        $I->sendPost('/api/register', $this->registration('connectable', 'connectable@example.com'));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $stored = $I->grabEntityFromRepository(User::class, ['nickname' => 'connectable']);
        $I->assertNotSame('securepassword123', $stored->getPassword(), 'le mot de passe n\'est jamais stocké en clair');
        $I->assertTrue($I->grabService(UserPasswordHasherInterface::class)->isPasswordValid($stored, 'securepassword123'));
    }

    public function aRegistrationWithoutVisibilityIsPrivate(ApiTester $I): void
    {
        // 1. 'Arrange'
        $registration = $this->registration('discret', 'discret@example.com');
        unset($registration['public']);

        // 2. 'Act'
        $I->sendPost('/api/register', $registration);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseContainsJson(['nickname' => 'discret', 'public' => false]);
    }

    public function cannotRegisterWithoutAPassword(ApiTester $I): void
    {
        // 1. 'Arrange'
        $registration = $this->registration('sansmdp', 'sansmdp@example.com');
        unset($registration['plainPassword']);

        // 2. 'Act'
        $I->sendPost('/api/register', $registration);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->dontSeeInRepository(User::class, ['nickname' => 'sansmdp']);
    }

    /**
     * Un entier désigne une chaîne de cette longueur.
     *
     * @param Example<int, string|int> $example
     */
    #[Examples('nickname', '')]
    #[Examples('nickname', 'ab')]
    #[Examples('nickname', 51)]
    #[Examples('firstname', '')]
    #[Examples('lastname', 101)]
    #[Examples('email', 51)]
    #[Examples('phone', 13)]
    #[Examples('description', 151)]
    #[Examples('plainPassword', 'court')]
    public function cannotRegisterWithAValueOutOfBounds(ApiTester $I, Example $example): void
    {
        // 1. 'Arrange'
        $registration = $this->registration('borne', 'borne@example.com');
        $registration[$example[0]] = is_int($example[1]) ? str_repeat('a', $example[1]) : $example[1];

        // 2. 'Act'
        $I->sendPost('/api/register', $registration);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->assertContains($example[0], $I->grabDataFromResponseByJsonPath('$.violations[*].propertyPath'));
        $I->dontSeeInRepository(User::class, ['email' => 'borne@example.com']);
    }

    /**
     * @return array<string, mixed>
     */
    private function registration(string $nickname, string $email): array
    {
        return [
            'nickname' => $nickname,
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => $email,
            'birthDate' => '1995-06-15',
            'phone' => '0123456789',
            'plainPassword' => 'securepassword123',
            'public' => true,
            'gender' => Gender::MAN->value,
        ];
    }
}
