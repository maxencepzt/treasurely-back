<?php

declare(strict_types=1);

namespace App\Tests\Api\Admin;

use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

final class AdminAccessCest
{
    use AdminWorld;

    public function aPlainUserIsRefused(ApiTester $I): void
    {
        $I->amLoggedInAs(UserFactory::createOne()->_real(), 'main');
        $I->sendGet('/admin');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function anAdministratorSeesTheMenu(ApiTester $I): void
    {
        $I->amLoggedInAs($this->admin(), 'main');
        $I->sendGet('/admin');
        $I->seeResponseCodeIs(HttpCode::OK);
        foreach (['Chasses au trésor', 'Types de chasse', 'Énigmes', 'Utilisateurs', 'Équipes', 'Images'] as $entry) {
            $I->seeResponseContains($entry);
        }
    }
}
