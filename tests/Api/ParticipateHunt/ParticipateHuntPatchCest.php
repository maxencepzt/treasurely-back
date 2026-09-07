<?php

declare(strict_types=1);

namespace App\Tests\Api\ParticipateHunt;

use App\Entity\ParticipateHunt;
use App\Entity\Riddle;
use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

/**
 * PATCH /participate_hunts/{id} : la note est le seul champ que le joueur écrit.
 * Progression, score et temps viennent du serveur et ignorent ce que le client envoie.
 */
final class ParticipateHuntPatchCest
{
    private function progress(ApiTester $I): ParticipateHunt
    {
        DesignerTeamFactory::createOne(['owner' => UserFactory::createOne()]); // la factory de chasse en tire une au hasard
        HuntTypeFactory::createOne();
        $hunt = TreasureHuntFactory::createOne(['riddleCount' => 2])->_real();
        $first = TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 1])->_real();
        TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 2]);
        $progress = (new ParticipateHunt())
            ->setHunter(UserFactory::createOne()->_real())
            ->setHunt($hunt)
            ->setCurrentRiddle($first)
            ->setLastParticipate(new \DateTimeImmutable());
        $I->haveInRepository($progress);

        return $progress;
    }

    public function theRateIsTheOnlyWritableField(ApiTester $I): void
    {
        $progress = $this->progress($I);
        $second = $I->grabEntityFromRepository(Riddle::class, ['hunt' => $progress->getHunt(), 'orderNumber' => 2]);

        $I->amLoggedInAs($progress->getHunter());
        $I->sendPatch('/api/participate_hunts/'.$progress->getId(), [
            'rate' => 4,
            'score' => 99999,
            'time' => 1,
            'finished' => true,
            'currentRiddle' => '/api/riddles/'.$second->getId(),
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['rate' => 4, 'score' => 0, 'time' => 0, 'finished' => false, 'riddlesSolved' => 0]);
        $I->assertSame(1, $progress->getCurrentRiddle()->getOrderNumber());
    }

    public function aStrangerCannotRate(ApiTester $I): void
    {
        $progress = $this->progress($I);

        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendPatch('/api/participate_hunts/'.$progress->getId(), ['rate' => 4]);

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->assertNull($progress->getRate());
    }
}
