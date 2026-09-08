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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
        // Seuls les champs publics (user:read) sont retournés après la mise à jour
        $I->seeResponseContainsJson(['nickname' => 'newnickname']);
        // Les champs sensibles ne sont pas retournés
        $I->dontSeeResponseJsonMatchesJsonPath('$.firstname');
        $I->dontSeeResponseJsonMatchesJsonPath('$.lastname');
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
            'nickname' => 'motdepasse',
            'password' => 'oldpassword',
        ])->_real();

        $updatedData = [
            'plainPassword' => 'newpassword123',
        ];

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPatch('/api/users/'.$user->getId(), $updatedData);

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->grabService(EntityManagerInterface::class)->clear();
        $fresh = $I->grabEntityFromRepository(User::class, ['id' => $user->getId()]);
        $hasher = $I->grabService(UserPasswordHasherInterface::class);
        $I->assertTrue($hasher->isPasswordValid($fresh, 'newpassword123'));
        $I->assertFalse($hasher->isPasswordValid($fresh, 'oldpassword'));
        $I->sendPost('/api/auth', ['nickname' => 'motdepasse', 'password' => 'newpassword123']);
        $I->seeResponseCodeIs(HttpCode::OK);
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
    #[Examples('firstname', 101)]
    #[Examples('lastname', '')]
    #[Examples('lastname', 101)]
    #[Examples('email', '')]
    #[Examples('email', 51)]
    #[Examples('phone', 13)]
    #[Examples('description', 151)]
    #[Examples('plainPassword', 'court')]
    public function cannotUpdateWithAValueOutOfBounds(ApiTester $I, Example $example): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne()->_real();
        $value = is_int($example[1]) ? str_repeat('a', $example[1]) : $example[1];

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPatch('/api/users/'.$user->getId(), [$example[0] => $value]);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->assertContains($example[0], $I->grabDataFromResponseByJsonPath('$.violations[*].propertyPath'));
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
        // Seuls les champs publics (user:read) sont retournés
        $I->seeResponseContainsJson([
            'nickname' => 'newnickname',
            'public' => true,
            'gender' => Gender::WOMAN->value,
        ]);
        // Les champs sensibles ne sont pas retournés dans user:read
        $I->dontSeeResponseJsonMatchesJsonPath('$.firstname');
        $I->dontSeeResponseJsonMatchesJsonPath('$.lastname');
        $I->dontSeeResponseJsonMatchesJsonPath('$.email');
        $I->dontSeeResponseJsonMatchesJsonPath('$.phone');
    }

    public function theTotalsCannotBeWrittenThroughTheApi(ApiTester $I): void
    {
        // 1. 'Arrange': les totaux sont dénormalisés par les listeners, jamais saisis
        $user = UserFactory::createOne(['totalScore' => 10, 'totalRiddles' => 2])->_real();

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPatch('/api/users/'.$user->getId(), ['totalScore' => 99999, 'totalRiddles' => 500]);

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson(['totalScore' => 10, 'totalRiddles' => 2]);
        $I->seeInRepository(User::class, ['id' => $user->getId(), 'totalScore' => 10, 'totalRiddles' => 2]);
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

    public function updatedUserIdRemainsUnchanged(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne()->_real();
        $originalId = $user->getId();

        $updatedData = [
            'nickname' => 'newnickname',
            'firstname' => 'New',
        ];

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPatch('/api/users/'.$user->getId(), $updatedData);

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['id' => $originalId]);
    }

    public function partialUpdateOnlyChangesSpecifiedFields(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne([
            'nickname' => 'originalnickname',
            'firstname' => 'OriginalFirstname',
            'lastname' => 'OriginalLastname',
        ])->_real();

        $updatedData = [
            'nickname' => 'newnickname',
        ];

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendPatch('/api/users/'.$user->getId(), $updatedData);

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['nickname' => 'newnickname']);

        // Vérifier que les autres champs n'ont pas été modifiés en base de données
        $I->seeInRepository(User::class, [
            'id' => $user->getId(),
            'firstname' => 'OriginalFirstname',
            'lastname' => 'OriginalLastname',
        ]);
    }
}
