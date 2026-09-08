<?php

declare(strict_types=1);

namespace App\Tests\Api\Team;

use App\Entity\ParticipateHunt;
use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\ParticipateHuntFactory;
use App\Factory\PlayerTeamFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Doctrine\ORM\EntityManagerInterface;

final class PlayerTeamRemovalCest
{
    public function deletingAPlayerTeamKeepsItsParticipations(ApiTester $I): void
    {
        // 1. 'Arrange'
        HuntTypeFactory::createOne();
        $owner = UserFactory::createOne()->_real();
        $hunt = TreasureHuntFactory::createOne(['owner' => $owner, 'designerTeam' => DesignerTeamFactory::createOne(['owner' => $owner]), 'riddleCount' => 1])->_real();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 1])->_real();
        $player = UserFactory::createOne()->_real();
        $team = PlayerTeamFactory::createOne(['owner' => $player, 'code' => 'treasurely_0000000000003'])->_real();
        $participation = ParticipateHuntFactory::createOne(['hunter' => $player, 'hunt' => $hunt, 'playerTeam' => $team, 'currentRiddle' => $riddle])->_real();
        $participationId = $participation->getId();

        // 2. 'Act'
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->remove($team);
        $entityManager->flush();
        $entityManager->clear();

        // 3. 'Assert'
        $kept = $entityManager->find(ParticipateHunt::class, $participationId);
        $I->assertNotNull($kept, 'the participation outlives its team');
        $I->assertNull($kept->getPlayerTeam());
    }
}
