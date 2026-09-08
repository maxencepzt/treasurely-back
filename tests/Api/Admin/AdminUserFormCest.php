<?php

declare(strict_types=1);

namespace App\Tests\Api\Admin;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminUserFormCest
{
    use AdminWorld;

    public function anAdministratorCreatesAUserWithAPassword(ApiTester $I): void
    {
        // 1. 'Arrange'
        $I->amLoggedInAs($this->admin(), 'main');
        $I->amOnPage('/admin/user/new');

        // 2. 'Act'
        $I->submitForm('form[name="User"]', [
            'ea[newForm][btn]' => 'saveAndReturn',
            'User[nickname]' => 'cree_par_admin',
            'User[plainPassword]' => 'Secret123!',
            'User[firstname]' => 'Test',
            'User[lastname]' => 'Bout',
            'User[email]' => 'cree.par.admin@example.com',
            'User[phone]' => '0600000000',
            'User[birthDate]' => '1990-01-01',
            'User[gender]' => '0',
        ]);

        // 3. 'Assert'
        $I->seeInCurrentUrl('/admin/user');
        $user = $I->grabEntityFromRepository(User::class, ['nickname' => 'cree_par_admin']);
        $I->assertTrue($I->grabService(UserPasswordHasherInterface::class)->isPasswordValid($user, 'Secret123!'));
        $I->assertFalse($user->isPublic());
    }

    public function editingWithoutAPasswordKeepsTheCurrentOne(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne(['nickname' => 'inchange'])->_real();
        $hashBefore = $user->getPassword();
        $I->amLoggedInAs($this->admin(), 'main');
        $I->amOnPage('/admin/user/'.$user->getId().'/edit');

        // 2. 'Act'
        $I->submitForm('form[name="User"]', ['ea[editForm][btn]' => 'saveAndReturn', 'User[firstname]' => 'Renommé']);

        // 3. 'Assert'
        $I->seeInCurrentUrl('/admin');
        $I->grabService(EntityManagerInterface::class)->clear();
        $fresh = $I->grabEntityFromRepository(User::class, ['nickname' => 'inchange']);
        $I->assertSame('Renommé', $fresh->getFirstname());
        $I->assertSame($hashBefore, $fresh->getPassword());
    }

    public function editingWithAPasswordReplacesIt(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne(['nickname' => 'change'])->_real();
        $I->amLoggedInAs($this->admin(), 'main');
        $I->amOnPage('/admin/user/'.$user->getId().'/edit');

        // 2. 'Act'
        $I->submitForm('form[name="User"]', ['ea[editForm][btn]' => 'saveAndReturn', 'User[plainPassword]' => 'Nouveau456!']);

        // 3. 'Assert'
        $I->seeInCurrentUrl('/admin');
        $I->grabService(EntityManagerInterface::class)->clear();
        $fresh = $I->grabEntityFromRepository(User::class, ['nickname' => 'change']);
        $hasher = $I->grabService(UserPasswordHasherInterface::class);
        $I->assertTrue($hasher->isPasswordValid($fresh, 'Nouveau456!'));
        $I->assertFalse($hasher->isPasswordValid($fresh, 'test'));
    }
}
