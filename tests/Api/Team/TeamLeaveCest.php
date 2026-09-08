<?php

declare(strict_types=1);

namespace App\Tests\Api\Team;

use App\Entity\PlayerTeam;
use App\Factory\PlayerTeamFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * POST /teams/{id}/leave : un membre part, le propriétaire reste (il supprime l'équipe).
 */
final class TeamLeaveCest
{
    public function aMemberCanLeave(ApiTester $I): void
    {
        // 1. 'Arrange' : le membre est créé après l'équipe puis ajouté à la main
        $team = PlayerTeamFactory::createOne(['owner' => UserFactory::createOne()])->_real();
        $member = UserFactory::createOne()->_real();
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $team->addMember($member);
        $entityManager->flush();

        // 2. 'Act'
        $I->amLoggedInAs($member);
        $I->sendPost('/api/teams/'.$team->getId().'/leave');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $entityManager->clear();
        $fresh = $I->grabEntityFromRepository(PlayerTeam::class, ['id' => $team->getId()]);
        $I->assertFalse($fresh->getMembers()->exists(fn (int $key, object $user) => $user->getId() === $member->getId()));
    }

    public function theOwnerCannotLeave(ApiTester $I): void
    {
        // 1. 'Arrange'
        $owner = UserFactory::createOne()->_real();
        $team = PlayerTeamFactory::createOne(['owner' => $owner])->_real();

        // 2. 'Act'
        $I->amLoggedInAs($owner);
        $I->sendPost('/api/teams/'.$team->getId().'/leave');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeResponseContainsJson(['detail' => 'Le créateur ne peut pas quitter son équipe : il peut la supprimer.']);
    }

    public function aStrangerCannotLeave(ApiTester $I): void
    {
        // 1. 'Arrange'
        $team = PlayerTeamFactory::createOne(['owner' => UserFactory::createOne()])->_real();
        $stranger = UserFactory::createOne()->_real();

        // 2. 'Act'
        $I->amLoggedInAs($stranger);
        $I->sendPost('/api/teams/'.$team->getId().'/leave');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
    }
}
