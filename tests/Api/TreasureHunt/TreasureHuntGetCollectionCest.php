<?php

declare(strict_types=1);

namespace App\Tests\Api\TreasureHunt;

use App\Entity\TreasureHunt;
use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

/**
 * GET /treasure_hunts sert à trouver une chasse à jouer : un joueur n'y voit que les ouvertes.
 */
final class TreasureHuntGetCollectionCest
{
    public function aPlayerOnlySeesOpenedHunts(ApiTester $I): void
    {
        // 1. 'Arrange'
        $this->oneHuntPerStatus();

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/treasure_hunts');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['totalItems' => 1]);
        $I->assertSame(['Ouverte'], $I->grabDataFromResponseByJsonPath('$.member[*].title'));
    }

    public function anAdminSeesEveryStatus(ApiTester $I): void
    {
        // 1. 'Arrange'
        $this->oneHuntPerStatus();

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne(['roles' => ['ROLE_ADMIN']])->_real());
        $I->sendGet('/api/treasure_hunts');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertEqualsCanonicalizing(['Ouverte', 'Fermée', 'Brouillon'], $I->grabDataFromResponseByJsonPath('$.member[*].title'));
    }

    private function oneHuntPerStatus(): void
    {
        DesignerTeamFactory::createOne(['owner' => UserFactory::createOne()]); // la factory de chasse en tire une au hasard
        HuntTypeFactory::createOne();
        TreasureHuntFactory::createOne(['title' => 'Ouverte', 'status' => TreasureHunt::STATE_OPENED]);
        TreasureHuntFactory::createOne(['title' => 'Fermée', 'status' => TreasureHunt::STATE_CLOSED]);
        TreasureHuntFactory::createOne(['title' => 'Brouillon', 'status' => TreasureHunt::STATE_DRAFT]);
    }
}
