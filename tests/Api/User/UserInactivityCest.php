<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Entity\User;
use App\Factory\PlayerTeamFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * La durée de conservation annoncée aux joueurs : la dernière activité est datée à chaque
 * JWT émis, et app:users:purge-inactive supprime les comptes muets depuis trois ans.
 */
final class UserInactivityCest
{
    public function aLoginRecordsTheLastActivity(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne(['nickname' => 'chasseur', 'password' => 'Secret123!', 'lastLogin' => new \DateTime('2020-01-01')])->_real();

        // 2. 'Act'
        $I->sendPost('/api/auth', ['nickname' => 'chasseur', 'password' => 'Secret123!']);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertGreaterThan(new \DateTime('-1 minute'), $this->lastLogin($I, $user));
    }

    /** Une session restée ouverte ne repasse pas par le mot de passe : le rafraîchissement compte aussi. */
    public function aTokenRefreshRecordsTheLastActivity(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne(['nickname' => 'chasseur', 'password' => 'Secret123!'])->_real();
        $I->sendPost('/api/auth', ['nickname' => 'chasseur', 'password' => 'Secret123!']);
        $refreshToken = $I->grabDataFromResponseByJsonPath('$.refresh_token')[0];
        $user = $I->grabEntityFromRepository(User::class, ['id' => $user->getId()]);
        $user->setLastLogin(new \DateTime('2020-01-01'));
        $I->grabService(EntityManagerInterface::class)->flush();

        // 2. 'Act'
        $I->sendPost('/api/token/refresh', ['refresh_token' => $refreshToken]);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertGreaterThan(new \DateTime('-1 minute'), $this->lastLogin($I, $user));
    }

    public function thePurgeDeletesOnlyPlainAccountsSilentForThreeYears(ApiTester $I): void
    {
        // 1. 'Arrange'
        $old = new \DateTime('-3 years -1 day');
        $silent = UserFactory::createOne(['nickname' => 'muet', 'lastLogin' => $old])->_real()->getId();
        $recent = UserFactory::createOne(['nickname' => 'actif', 'lastLogin' => new \DateTime('-2 years')])->_real()->getId();
        $admin = UserFactory::createOne(['nickname' => 'admin', 'lastLogin' => $old, 'roles' => ['ROLE_ADMIN']])->_real()->getId();
        $owner = UserFactory::createOne(['nickname' => 'capitaine', 'lastLogin' => $old])->_real();
        PlayerTeamFactory::createOne(['owner' => $owner]);

        // 2. 'Act'
        $output = $I->runSymfonyConsoleCommand('app:users:purge-inactive');

        // 3. 'Assert'
        $I->dontSeeInRepository(User::class, ['id' => $silent]);
        $I->seeInRepository(User::class, ['id' => $recent]);
        $I->seeInRepository(User::class, ['id' => $admin]);
        $I->seeInRepository(User::class, ['id' => $owner->getId()]);
        $I->assertStringContainsString('capitaine', $output);
    }

    public function aDryRunDeletesNothing(ApiTester $I): void
    {
        // 1. 'Arrange'
        $silent = UserFactory::createOne(['nickname' => 'muet', 'lastLogin' => new \DateTime('-4 years')])->_real()->getId();

        // 2. 'Act'
        $output = $I->runSymfonyConsoleCommand('app:users:purge-inactive', ['--dry-run' => true]);

        // 3. 'Assert'
        $I->seeInRepository(User::class, ['id' => $silent]);
        $I->assertStringContainsString('muet', $output);
    }

    private function lastLogin(ApiTester $I, User $user): \DateTime
    {
        $I->grabService(EntityManagerInterface::class)->clear();

        return $I->grabEntityFromRepository(User::class, ['id' => $user->getId()])->getLastLogin();
    }
}
