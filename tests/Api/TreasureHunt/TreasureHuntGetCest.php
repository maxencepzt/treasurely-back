<?php

declare(strict_types=1);

namespace App\Tests\Api\TreasureHunt;

use App\Entity\TreasureHunt;
use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Examples;
use Codeception\Example;
use Codeception\Util\HttpCode;

/**
 * GET /treasure_hunts/{id} : une chasse ouverte ou fermée se lit, un brouillon seulement par
 * ses concepteurs, la règle de TreasureHuntVoter (le membre de l'équipe n'en fait pas partie).
 */
final class TreasureHuntGetCest
{
    /**
     * @param Example<int, string|int> $example
     */
    #[Examples('player', TreasureHunt::STATE_OPENED, HttpCode::OK)]
    #[Examples('player', TreasureHunt::STATE_CLOSED, HttpCode::OK)]
    #[Examples('player', TreasureHunt::STATE_DRAFT, HttpCode::FORBIDDEN)]
    #[Examples('member', TreasureHunt::STATE_DRAFT, HttpCode::FORBIDDEN)]
    #[Examples('owner', TreasureHunt::STATE_DRAFT, HttpCode::OK)]
    #[Examples('admin', TreasureHunt::STATE_DRAFT, HttpCode::OK)]
    public function whoReadsWhichStatus(ApiTester $I, Example $example): void
    {
        // 1. 'Arrange'
        $owner = UserFactory::createOne()->_real();
        $member = UserFactory::createOne()->_real();
        $team = DesignerTeamFactory::createOne(['owner' => $owner, 'members' => [$member]])->_real();
        HuntTypeFactory::createOne();
        $hunt = TreasureHuntFactory::createOne(['designerTeam' => $team, 'owner' => $owner, 'status' => $example[1], 'title' => 'Lue'])->_real();
        $reader = match ($example[0]) {
            'owner' => $owner,
            'member' => $member,
            'admin' => UserFactory::createOne(['roles' => ['ROLE_ADMIN']])->_real(),
            default => UserFactory::createOne()->_real(),
        };

        // 2. 'Act'
        $I->amLoggedInAs($reader);
        $I->sendGet('/api/treasure_hunts/'.$hunt->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIs((int) $example[2]);
        if (HttpCode::OK === $example[2]) {
            $I->seeResponseContainsJson(['title' => 'Lue', 'status' => $example[1]]);
        }
    }
}
