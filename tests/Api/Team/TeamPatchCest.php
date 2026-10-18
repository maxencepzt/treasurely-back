<?php

declare(strict_types=1);

namespace App\Tests\Api\Team;

use App\Factory\PlayerTeamFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Examples;
use Codeception\Example;
use Codeception\Util\HttpCode;

final class TeamPatchCest
{
    /**
     * Régression : sans contrainte, un nom vide passait et un nom trop long finissait en 500.
     * Un entier désigne une chaîne de cette longueur.
     *
     * @param Example<int, string|int> $example
     */
    #[Examples('name', '')]
    #[Examples('name', 101)]
    #[Examples('description', 501)]
    public function cannotRenameATeamOutOfBounds(ApiTester $I, Example $example): void
    {
        // 1. 'Arrange'
        $owner = UserFactory::createOne()->_real();
        $team = PlayerTeamFactory::createOne(['owner' => $owner])->_real();
        $value = is_int($example[1]) ? str_repeat('a', $example[1]) : $example[1];

        // 2. 'Act'
        $I->amLoggedInAs($owner);
        $I->sendPatch('/api/teams/'.$team->getId(), [$example[0] => $value]);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->assertContains($example[0], $I->grabDataFromResponseByJsonPath('$.violations[*].propertyPath'));
    }
}
