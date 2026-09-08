<?php

declare(strict_types=1);

namespace App\Tests\Api\Admin;

use App\Entity\ParticipateHunt;
use App\Entity\Team;
use App\Entity\User;
use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;
use Doctrine\ORM\EntityManagerInterface;

final class AdminDeletionCest
{
    use AdminWorld;

    public function aDeletionBlockedByAForeignKeyComesBackWithAMessage(ApiTester $I): void
    {
        // 1. 'Arrange': un propriétaire de chasse dont l'équipe conceptrice appartient à un autre
        HuntTypeFactory::createOne();
        $designer = UserFactory::createOne()->_real();
        $team = DesignerTeamFactory::createOne(['owner' => $designer])->_real();
        $owner = UserFactory::createOne(['nickname' => 'proprietaire'])->_real();
        TreasureHuntFactory::createOne(['owner' => $owner, 'designerTeam' => $team, 'riddleCount' => 0]);
        $I->amLoggedInAs($this->admin(), 'main');

        // 2. 'Act'
        $this->delete($I, '/admin/user', $owner->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertStringContainsString('« proprietaire » ne peut pas être supprimé', html_entity_decode((string) $I->grabResponse(), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $I->seeInRepository(User::class, ['id' => $owner->getId()]);
    }

    public function aPlayerTeamThatPlayedCanBeDeleted(ApiTester $I): void
    {
        // 1. 'Arrange'
        $world = $this->world($I);
        $teamId = $world['playerTeam']->getId();
        $participationId = $world['participation']->getId();
        $I->amLoggedInAs($world['admin'], 'main');

        // 2. 'Act'
        $this->delete($I, '/admin/team', $teamId);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->dontSeeInRepository(Team::class, ['id' => $teamId]);
        $I->grabService(EntityManagerInterface::class)->clear();
        $kept = $I->grabEntityFromRepository(ParticipateHunt::class, ['id' => $participationId]);
        $I->assertNull($kept->getPlayerTeam());
    }

    public function aPlayerWithParticipationsCanBeDeleted(ApiTester $I): void
    {
        // 1. 'Arrange'
        $world = $this->world($I);
        $playerId = $world['player']->getId();
        $I->amLoggedInAs($world['admin'], 'main');

        // 2. 'Act'
        $this->delete($I, '/admin/user', $playerId);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->dontSeeInRepository(User::class, ['id' => $playerId]);
    }

    /**
     * Rejoue ce que fait le bouton « Supprimer » : le jeton `ea-delete` est lu sur la liste, la
     * suppression est un POST, et la page suivie est la liste avec ses messages.
     */
    private function delete(ApiTester $I, string $list, int $id): void
    {
        $I->sendGet($list);
        $I->seeResponseCodeIs(HttpCode::OK);
        preg_match('/name="token" value="([^"]+)"/', (string) $I->grabResponse(), $matches);
        $I->assertNotEmpty($matches, 'Jeton de suppression absent de la liste');

        $I->sendFormPost($list.'/'.$id.'/delete', ['token' => $matches[1]]);
        if (null !== $I->grabHttpHeader('Location')) {
            $I->sendGet($list);
        }
    }
}
