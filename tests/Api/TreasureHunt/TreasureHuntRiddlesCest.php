<?php

declare(strict_types=1);

namespace App\Tests\Api\TreasureHunt;

use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

/**
 * GET /treasure_hunts/{id}/riddles liste le parcours sans les énoncés ni les solutions :
 * l'énoncé ne se lit que sur l'énigme, dont la lecture démarre le chronomètre.
 */
final class TreasureHuntRiddlesCest
{
    public function theListCarriesNeitherStatementsNorSolutions(ApiTester $I): void
    {
        DesignerTeamFactory::createOne(['owner' => UserFactory::createOne()]); // la factory de chasse en tire une au hasard
        HuntTypeFactory::createOne();
        $hunt = TreasureHuntFactory::createOne()->_real();
        TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 1, 'title' => 'Première', 'description' => 'Énoncé secret', 'answer' => 'cathédrale']);

        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/treasure_hunts/'.$hunt->getId().'/riddles');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['riddles' => [['title' => 'Première', 'type' => 'text', 'orderNumber' => 1]]]);
        $I->dontSeeResponseContains('secret');
        $I->dontSeeResponseContains('cath');
    }
}
