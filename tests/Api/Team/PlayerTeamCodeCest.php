<?php

declare(strict_types=1);

namespace App\Tests\Api\Team;

use App\Entity\PlayerTeam;
use App\Factory\PlayerTeamFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PlayerTeamCodeCest
{
    public function aPlayerTeamValidatesWithItsOwnCode(ApiTester $I): void
    {
        // 1. 'Arrange'
        $team = PlayerTeamFactory::createOne(['owner' => UserFactory::createOne(), 'code' => 'treasurely_0000000000001'])->_real();

        // 2. 'Act'
        $violations = $I->grabService(ValidatorInterface::class)->validate($team);

        // 3. 'Assert'
        $I->assertCount(0, $violations);
    }

    public function twoPlayerTeamsCannotShareAJoinCode(ApiTester $I): void
    {
        // 1. 'Arrange'
        $owner = UserFactory::createOne()->_real();
        PlayerTeamFactory::createOne(['owner' => $owner, 'code' => 'treasurely_0000000000002']);
        $duplicate = (new PlayerTeam())
            ->setName('Doublon')
            ->setDescription('Même code de jointure')
            ->setOwner($owner)
            ->setCode('treasurely_0000000000002');

        // 2. 'Act'
        $violations = $I->grabService(ValidatorInterface::class)->validate($duplicate);

        // 3. 'Assert'
        $I->assertCount(1, $violations);
        $I->assertSame('code', $violations[0]->getPropertyPath());
    }
}
