<?php

declare(strict_types=1);

namespace App\Tests\Api\Team;

use App\Entity\PlayerTeam;
use App\Entity\TeamJoinRequest;
use App\Enum\JoinRequestStatus;
use App\Factory\PlayerTeamFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les demandes d'adhésion faites depuis l'annuaire : une par joueur et par équipe, tranchées
 * par le propriétaire.
 */
final class TeamJoinRequestCest
{
    public function canAskToJoinATeam(ApiTester $I): void
    {
        // 1. 'Arrange' : le candidat est créé après l'équipe, la factory ne l'y a pas mis
        $team = PlayerTeamFactory::createOne(['owner' => UserFactory::createOne(), 'name' => 'Les Fouineurs'])->_real();
        $candidate = UserFactory::createOne(['nickname' => 'candidat'])->_real();

        // 2. 'Act'
        $I->amLoggedInAs($candidate);
        $I->sendPost('/api/player_teams/'.$team->getId().'/requests');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseContainsJson(['status' => 'pending', 'team' => ['name' => 'Les Fouineurs'], 'user' => ['nickname' => 'candidat']]);
        $I->seeInRepository(TeamJoinRequest::class, ['team' => $team, 'user' => $candidate, 'status' => JoinRequestStatus::PENDING]);
    }

    public function aMemberDoesNotAsk(ApiTester $I): void
    {
        // 1. 'Arrange' : le propriétaire est membre d'office
        $owner = UserFactory::createOne()->_real();
        $team = PlayerTeamFactory::createOne(['owner' => $owner])->_real();

        // 2. 'Act'
        $I->amLoggedInAs($owner);
        $I->sendPost('/api/player_teams/'.$team->getId().'/requests');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
    }

    public function aRefusedPlayerCannotAskAgain(ApiTester $I): void
    {
        // 1. 'Arrange'
        [$team, $candidate, $request] = $this->pendingRequest($I);
        $request->refuse();
        $I->grabService(EntityManagerInterface::class)->flush();

        // 2. 'Act'
        $I->amLoggedInAs($candidate);
        $I->sendPost('/api/player_teams/'.$team->getId().'/requests');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeResponseContainsJson(['detail' => 'Votre demande a été refusée.']);
    }

    public function theOwnerListsThePendingRequests(ApiTester $I): void
    {
        // 1. 'Arrange' : une demande en attente et une refusée
        [$team] = $this->pendingRequest($I, 'en_attente');
        $refused = new TeamJoinRequest($team, UserFactory::createOne(['nickname' => 'refuse'])->_real());
        $refused->refuse();
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist($refused);
        $entityManager->flush();

        // 2. 'Act'
        $I->amLoggedInAs($team->getOwner());
        $I->sendGet('/api/player_teams/'.$team->getId().'/requests');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertSame(['en_attente'], $I->grabDataFromResponseByJsonPath('$.member[*].user.nickname'));
    }

    public function aStrangerCannotListTheRequests(ApiTester $I): void
    {
        // 1. 'Arrange'
        [$team] = $this->pendingRequest($I);

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/player_teams/'.$team->getId().'/requests');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function theOwnerAcceptsAndThePlayerJoins(ApiTester $I): void
    {
        // 1. 'Arrange'
        [$team, $candidate, $request] = $this->pendingRequest($I);

        // 2. 'Act'
        $I->amLoggedInAs($team->getOwner());
        $I->sendPost('/api/team_requests/'.$request->getId().'/accept');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['status' => 'accepted']);
        $I->seeResponseJsonMatchesJsonPath('$.decidedAt');
        $I->grabService(EntityManagerInterface::class)->clear();
        $fresh = $I->grabEntityFromRepository(PlayerTeam::class, ['id' => $team->getId()]);
        $I->assertTrue($fresh->getMembers()->exists(fn (int $key, object $member) => $member->getId() === $candidate->getId()));
    }

    public function theOwnerRefusesAndThePlayerStaysOut(ApiTester $I): void
    {
        // 1. 'Arrange'
        [$team, $candidate, $request] = $this->pendingRequest($I);

        // 2. 'Act'
        $I->amLoggedInAs($team->getOwner());
        $I->sendPost('/api/team_requests/'.$request->getId().'/refuse');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['status' => 'refused']);
        $I->grabService(EntityManagerInterface::class)->clear();
        $fresh = $I->grabEntityFromRepository(PlayerTeam::class, ['id' => $team->getId()]);
        $I->assertFalse($fresh->getMembers()->exists(fn (int $key, object $member) => $member->getId() === $candidate->getId()));
    }

    public function onlyTheOwnerDecides(ApiTester $I): void
    {
        // 1. 'Arrange'
        [, $candidate, $request] = $this->pendingRequest($I);

        // 2. 'Act'
        $I->amLoggedInAs($candidate);
        $I->sendPost('/api/team_requests/'.$request->getId().'/accept');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function aDecidedRequestIsNotDecidedTwice(ApiTester $I): void
    {
        // 1. 'Arrange'
        [$team, , $request] = $this->pendingRequest($I);
        $request->refuse();
        $I->grabService(EntityManagerInterface::class)->flush();

        // 2. 'Act'
        $I->amLoggedInAs($team->getOwner());
        $I->sendPost('/api/team_requests/'.$request->getId().'/accept');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
    }

    public function theCandidateWithdraws(ApiTester $I): void
    {
        // 1. 'Arrange'
        [, $candidate, $request] = $this->pendingRequest($I);
        $id = $request->getId();

        // 2. 'Act'
        $I->amLoggedInAs($candidate);
        $I->sendDelete('/api/team_requests/'.$id);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->dontSeeInRepository(TeamJoinRequest::class, ['id' => $id]);
    }

    public function aStrangerCannotTouchARequest(ApiTester $I): void
    {
        // 1. 'Arrange'
        [, , $request] = $this->pendingRequest($I);

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/team_requests/'.$request->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function myRequestsAreListed(ApiTester $I): void
    {
        // 1. 'Arrange'
        [$team, $candidate] = $this->pendingRequest($I);

        // 2. 'Act'
        $I->amLoggedInAs($candidate);
        $I->sendGet('/api/me/team_requests');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['member' => [['status' => 'pending', 'team' => ['@id' => '/api/teams/'.$team->getId()]]]]);
    }

    /**
     * Une équipe, un candidat créé après elle, et sa demande en attente.
     *
     * @return array{0: PlayerTeam, 1: \App\Entity\User, 2: TeamJoinRequest}
     */
    private function pendingRequest(ApiTester $I, string $nickname = 'candidat'): array
    {
        $team = PlayerTeamFactory::createOne(['owner' => UserFactory::createOne()])->_real();
        $candidate = UserFactory::createOne(['nickname' => $nickname])->_real();
        $request = new TeamJoinRequest($team, $candidate);
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist($request);
        $entityManager->flush();

        return [$team, $candidate, $request];
    }
}
