<?php

declare(strict_types=1);

namespace App\Tests\Api\Designer;

use App\Entity\DesignerTeam;
use App\Factory\DesignerTeamFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

/**
 * Façade de conception, côté équipes : les actions exigent le jeton CSRF de la page, et la
 * recherche de membres ne livre que des pseudos.
 */
final class DesignerTeamCest
{
    /**
     * Une page d'un autre sous-domaine de maxencepzt.fr est du même site : le cookie de session
     * l'accompagne malgré SameSite=Lax, seul le jeton la distingue de la façade.
     */
    public function aTeamActionWithoutTheCsrfTokenIsRefused(ApiTester $I): void
    {
        // 1. 'Arrange'
        $owner = UserFactory::createOne()->_real();
        $team = DesignerTeamFactory::createOne(['owner' => $owner])->_real();

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendDelete('/designer/team/'.$team->getId().'/delete');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeInRepository(DesignerTeam::class, ['id' => $team->getId()]);
    }

    public function aTeamActionWithTheTokenOfItsPageGoesThrough(ApiTester $I): void
    {
        // 1. 'Arrange' : le jeton lu sur la page de l'équipe, comme le ferait team-details.js
        $owner = UserFactory::createOne()->_real();
        $team = DesignerTeamFactory::createOne(['owner' => $owner])->_real();
        $id = $team->getId();
        $I->amLoggedInAs($owner, 'main');
        $I->sendGet('/designer/team/'.$id.'/details');
        $I->seeResponseCodeIs(HttpCode::OK);
        preg_match('/data-csrf-token="([^"]+)"/', (string) $I->grabResponse(), $matches);
        $I->assertNotEmpty($matches, 'Jeton CSRF absent de la page de l\'équipe');

        // 2. 'Act'
        $I->haveHttpHeader('X-CSRF-Token', $matches[1]);
        $I->sendDelete('/designer/team/'.$id.'/delete');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->dontSeeInRepository(DesignerTeam::class, ['id' => $id]);
    }

    /** Tout joueur connecté peut ouvrir la façade : les noms réels n'en sortent pas. */
    public function theMemberSearchNeverExposesRealNames(ApiTester $I): void
    {
        // 1. 'Arrange'
        UserFactory::createOne(['nickname' => 'explorateur', 'firstname' => 'Camille', 'lastname' => 'Durand']);
        $I->amLoggedInAs(UserFactory::createOne()->_real(), 'main');

        // 2. 'Act'
        $I->sendGet('/designer/team/search-users', ['q' => 'Durand']);
        $I->seeResponseEquals('[]');
        $I->sendGet('/designer/team/search-users', ['q' => 'explo']);

        // 3. 'Assert'
        $I->seeResponseContainsJson([['nickname' => 'explorateur']]);
        $I->dontSeeResponseContains('Camille');
        $I->dontSeeResponseContains('Durand');
    }
}
