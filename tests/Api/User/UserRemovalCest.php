<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Entity\ParticipateRiddle;
use App\Entity\User;
use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\ParticipateHuntFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Doctrine\ORM\EntityManagerInterface;

final class UserRemovalCest
{
    public function aPlayerCanBeDeletedWithTheirParticipations(ApiTester $I): void
    {
        // 1. 'Arrange'
        HuntTypeFactory::createOne();
        $owner = UserFactory::createOne()->_real();
        $hunt = TreasureHuntFactory::createOne(['owner' => $owner, 'designerTeam' => DesignerTeamFactory::createOne(['owner' => $owner]), 'riddleCount' => 1])->_real();
        $riddle = TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 1])->_real();
        $player = UserFactory::createOne()->_real();
        ParticipateHuntFactory::createOne(['hunter' => $player, 'hunt' => $hunt, 'currentRiddle' => $riddle]);

        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist((new ParticipateRiddle())
            ->setHunter($player)
            ->setRiddle($riddle)
            ->setStartTime(new \DateTimeImmutable('-2 minutes'))
            ->setFinishTime(new \DateTimeImmutable())
            ->setLastParticipate(new \DateTime()));
        $entityManager->flush();
        $playerId = $player->getId();

        // 2. 'Act': the stats listeners must not refresh a user removed in the same flush
        $entityManager->remove($player);
        $entityManager->flush();

        // 3. 'Assert'
        $entityManager->clear();
        $I->assertNull($entityManager->find(User::class, $playerId));
    }
}
