<?php

declare(strict_types=1);

namespace App\Tests\Api\Admin;

use App\Entity\ParticipateHunt;
use App\Entity\ParticipateRiddle;
use App\Tests\Support\ApiTester;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les horloges du jeu sont posées par le serveur : le back office les montre, il ne les
 * réécrit pas, même en enregistrant le formulaire.
 */
final class AdminParticipationFormCest
{
    use AdminWorld;

    public function savingAHuntParticipationKeepsItsClock(ApiTester $I): void
    {
        // 1. 'Arrange'
        $world = $this->world($I);
        $participation = $world['participation'];
        $participation->setLastParticipate(new \DateTimeImmutable('2026-08-20 21:35:20'));
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->flush();
        $I->amLoggedInAs($world['admin'], 'main');
        $I->amOnPage('/admin/participate-hunt/'.$participation->getId().'/edit');
        $I->dontSeeElement('input[name="ParticipateHunt[lastParticipate]"]');

        // 2. 'Act'
        $I->submitForm('form[name="ParticipateHunt"]', ['ea[editForm][btn]' => 'saveAndReturn', 'ParticipateHunt[score]' => '999']);

        // 3. 'Assert'
        $I->seeInCurrentUrl('/admin');
        $entityManager->clear();
        $fresh = $I->grabEntityFromRepository(ParticipateHunt::class, ['id' => $participation->getId()]);
        $I->assertSame(999, $fresh->getScore());
        $I->assertSame('2026-08-20 21:35:20', $fresh->getLastParticipate()->format('Y-m-d H:i:s'));
    }

    public function savingARiddleParticipationKeepsItsClocks(ApiTester $I): void
    {
        // 1. 'Arrange'
        $world = $this->world($I);
        $participateRiddle = $world['participateRiddle'];
        $I->amLoggedInAs($world['admin'], 'main');
        $I->amOnPage('/admin/participate-riddle/'.$participateRiddle->getId().'/edit');
        foreach (['startTime', 'finishTime', 'lastParticipate'] as $clock) {
            $I->dontSeeElement('input[name="ParticipateRiddle['.$clock.']"]');
        }

        // 2. 'Act'
        $I->submitForm('form[name="ParticipateRiddle"]', ['ea[editForm][btn]' => 'saveAndReturn', 'ParticipateRiddle[score]' => '42']);

        // 3. 'Assert'
        $I->seeInCurrentUrl('/admin');
        $I->grabService(EntityManagerInterface::class)->clear();
        $fresh = $I->grabEntityFromRepository(ParticipateRiddle::class, ['id' => $participateRiddle->getId()]);
        $I->assertSame(42, $fresh->getScore());
        $I->assertSame('2026-09-08 02:24:46', $fresh->getStartTime()->format('Y-m-d H:i:s'));
        $I->assertSame('2026-09-08 02:26:24', $fresh->getLastParticipate()->format('Y-m-d H:i:s'));
    }
}
