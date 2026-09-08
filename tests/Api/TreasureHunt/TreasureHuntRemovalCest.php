<?php

declare(strict_types=1);

namespace App\Tests\Api\TreasureHunt;

use App\Entity\ParticipateRiddle;
use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Doctrine\ORM\EntityManagerInterface;

final class TreasureHuntRemovalCest
{
    public function deletingAHuntRecomputesThePlayersRiddleTotal(ApiTester $I): void
    {
        // 1. 'Arrange'
        HuntTypeFactory::createOne();
        $owner = UserFactory::createOne()->_real();
        $team = DesignerTeamFactory::createOne(['owner' => $owner])->_real();
        $hunt = TreasureHuntFactory::createOne(['owner' => $owner, 'designerTeam' => $team, 'riddleCount' => 1])->_real();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 1])->_real();
        $player = UserFactory::createOne()->_real();

        $entityManager = $I->grabService(EntityManagerInterface::class);
        $solved = (new ParticipateRiddle())
            ->setHunter($player)
            ->setRiddle($riddle)
            ->setStartTime(new \DateTimeImmutable('-2 minutes'))
            ->setFinishTime(new \DateTimeImmutable())
            ->setLastParticipate(new \DateTime());
        $entityManager->persist($solved);
        $entityManager->flush();
        $I->assertSame(1, $player->getTotalRiddles(), 'the listener counts the solved riddle');
        $solvedId = $solved->getId();

        // 2. 'Act'
        $entityManager->remove($hunt);
        $entityManager->flush();

        // 3. 'Assert'
        $entityManager->refresh($player);
        $I->assertSame(0, $player->getTotalRiddles());
        $I->assertNull($entityManager->find(ParticipateRiddle::class, $solvedId));
    }
}
