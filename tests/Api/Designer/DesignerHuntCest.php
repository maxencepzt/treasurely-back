<?php

declare(strict_types=1);

namespace App\Tests\Api\Designer;

use App\Entity\MCQRiddle;
use App\Entity\ParticipateRiddle;
use App\Entity\Riddle;
use App\Entity\TextRiddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\MCQRiddleFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

/**
 * Façade de conception : la modification d'une chasse doit préserver les énigmes
 * non touchées, et avec elles la progression des joueurs.
 */
final class DesignerHuntCest
{
    /**
     * @return array{owner: User, hunt: TreasureHunt, riddles: Riddle[]}
     */
    private function huntWithTwoRiddles(string $status = TreasureHunt::STATE_OPENED): array
    {
        $owner = UserFactory::createOne()->_real();
        $team = DesignerTeamFactory::createOne(['owner' => $owner])->_real();
        $team->addMember($owner);
        HuntTypeFactory::createOne(); // la factory de chasse en tire un au hasard
        $hunt = TreasureHuntFactory::createOne([
            'owner' => $owner,
            'designerTeam' => $team,
            'status' => $status,
            'difficulty' => 3,
            'title' => 'Vieux Reims',
        ])->_real();
        $riddles = [
            TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 1, 'title' => 'Première', 'answer' => 'cathédrale'])->_real(),
            MCQRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 2, 'title' => 'Seconde', 'choices' => ['a', 'b', 'c'], 'answers' => ['a']])->_real(),
        ];

        return ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles];
    }

    /**
     * Le jeton CSRF est lu sur une page de formulaire, comme le ferait le navigateur :
     * il est lié à la session que la requête suivante réutilise. La page de création
     * est ouverte à tout concepteur connecté, ce qui vaut aussi pour un intrus.
     */
    private function csrfToken(ApiTester $I): string
    {
        $I->sendGet('/designer/hunt/create');
        $I->seeResponseCodeIs(HttpCode::OK);
        preg_match('/data-csrf-token="([^"]+)"/', (string) $I->grabResponse(), $matches);
        $I->assertNotEmpty($matches, 'Jeton CSRF absent de la page de création');

        return $matches[1];
    }

    /**
     * @param array<int, Riddle|array<string, mixed>> $riddles   entités existantes ou énigmes nouvelles
     * @param array<string, mixed>                    $overrides
     *
     * @return array<string, mixed>
     */
    private function payload(ApiTester $I, TreasureHunt $hunt, array $riddles, array $overrides = []): array
    {
        $riddleData = [];
        foreach ($riddles as $riddle) {
            $riddleData[] = $riddle instanceof Riddle ? $this->describe($riddle) : $riddle;
        }

        return array_merge([
            '_token' => $this->csrfToken($I),
            'name' => $hunt->getTitle(),
            'description' => $hunt->getDescription(),
            'designer_team_id' => $hunt->getDesignerTeam()?->getId(),
            'hunt_types' => json_encode([]),
            'difficulty' => $hunt->getDifficulty(),
            'estimated_duration' => 60,
            'city' => 'Reims',
            'action' => 'draft',
            'riddles' => json_encode($riddleData),
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(Riddle $riddle): array
    {
        $data = [
            'id' => $riddle->getId(),
            'type' => $riddle->getType(),
            'title' => $riddle->getTitle(),
            'description' => $riddle->getDescription(),
            'difficulty' => $riddle->getDifficulty(),
            'maxScoringAttempts' => $riddle->getMaxScoringAttempts(),
        ];
        if ($riddle instanceof TextRiddle) {
            $data['answer'] = $riddle->getAnswer();
        }
        if ($riddle instanceof MCQRiddle) {
            $data['choices'] = $riddle->getChoices();
            $data['answers'] = $riddle->getAnswers();
        }

        return $data;
    }

    /**
     * Une participation minimale : la factory tire ses valeurs au hasard parmi des
     * objets qui n'existent pas dans ce test, on construit donc l'entité directement.
     */
    private function play(ApiTester $I, Riddle $riddle): ParticipateRiddle
    {
        $participation = (new ParticipateRiddle())
            ->setHunter(UserFactory::createOne()->_real())
            ->setRiddle($riddle)
            ->setStartTime(new \DateTimeImmutable())
            ->setLastParticipate(new \DateTime())
            ->setScore(0);
        $I->haveInRepository($participation);

        return $participation;
    }

    /**
     * @return array<string, mixed>
     */
    private function newTextRiddle(string $title): array
    {
        return ['type' => 'text', 'title' => $title, 'description' => 'Énoncé', 'difficulty' => 1, 'maxScoringAttempts' => 5, 'answer' => 'réponse'];
    }

    public function editingTheTitleKeepsPlayerProgress(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();
        $participation = $this->play($I, $riddles[0]);
        $riddleIds = array_map(static fn (Riddle $r): int => $r->getId(), $riddles);

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, $riddles, ['name' => 'Vieux Reims 2']));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInRepository(TreasureHunt::class, ['id' => $hunt->getId(), 'title' => 'Vieux Reims 2', 'difficulty' => 3]);
        $I->seeInRepository(ParticipateRiddle::class, ['id' => $participation->getId(), 'riddle' => $riddleIds[0]]);
        foreach ($riddleIds as $id) {
            $I->seeInRepository(Riddle::class, ['id' => $id]);
        }
    }

    public function aRiddleMissingFromThePayloadIsDeletedOnADraft(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles(TreasureHunt::STATE_DRAFT);
        $removedId = $riddles[1]->getId();

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, [$riddles[0]]));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->dontSeeInRepository(Riddle::class, ['id' => $removedId]);
        $I->seeInRepository(TreasureHunt::class, ['id' => $hunt->getId(), 'riddleCount' => 1]);
    }

    public function deletingAPlayedRiddleOfAnOpenedHuntIsRefused(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();
        $this->play($I, $riddles[1]);

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, [$riddles[0]]));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeResponseContainsJson(['error' => 'L\'énigme « Seconde » a déjà été jouée et ne peut plus être supprimée.']);
        $I->seeInRepository(Riddle::class, ['id' => $riddles[1]->getId()]);
    }

    public function changingTheTypeOfAPlayedRiddleIsRefused(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();
        $this->play($I, $riddles[0]);
        $retyped = $this->describe($riddles[0]);
        $retyped['type'] = 'qr';
        $retyped['code'] = 'CODE1';

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, [$retyped, $riddles[1]]));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeInRepository(TextRiddle::class, ['id' => $riddles[0]->getId()]);
    }

    public function aClosedHuntKeepsItsStructureButAcceptsMetadata(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles(TreasureHunt::STATE_CLOSED);
        $I->amLoggedInAs($owner, 'main');

        // 2. 'Act' : ajout refusé
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, [...$riddles, $this->newTextRiddle('Troisième')]));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CONFLICT);

        // 2. 'Act' : métadonnées acceptées
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, $riddles, ['city' => 'Épernay']));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInRepository(TreasureHunt::class, ['id' => $hunt->getId(), 'location' => 'Épernay', 'status' => TreasureHunt::STATE_CLOSED]);
    }

    public function newRiddlesAreCreatedAndOrderFollowsThePayload(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles(TreasureHunt::STATE_DRAFT);

        // 2. 'Act' : la nouvelle énigme passe en tête, la première existante est retirée
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, [$this->newTextRiddle('Troisième'), $riddles[1]]));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInRepository(Riddle::class, ['title' => 'Troisième', 'orderNumber' => 1, 'maxScoringAttempts' => 5, 'hunt' => $hunt->getId()]);
        $I->seeInRepository(Riddle::class, ['id' => $riddles[1]->getId(), 'orderNumber' => 2]);
        $I->dontSeeInRepository(Riddle::class, ['id' => $riddles[0]->getId()]);
    }

    public function publishingGoesThroughTheWorkflow(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles(TreasureHunt::STATE_DRAFT);

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, $riddles, ['action' => 'publish']));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInRepository(TreasureHunt::class, ['id' => $hunt->getId(), 'status' => TreasureHunt::STATE_OPENED]);
    }

    public function creatingAHuntPersistsItsRiddlesWithTheirSettings(ApiTester $I): void
    {
        // 1. 'Arrange'
        $owner = UserFactory::createOne()->_real();
        $team = DesignerTeamFactory::createOne(['owner' => $owner])->_real();
        $huntType = HuntTypeFactory::createOne()->_real();
        $mcq = ['type' => 'mcq', 'title' => 'Capitale', 'description' => 'Laquelle ?', 'difficulty' => 2, 'maxScoringAttempts' => 2,
            'choices' => ['Paris', 'Lyon', 'Reims'], 'answers' => ['Paris'], 'revealAnswerCount' => false];
        $hunt = (new TreasureHunt())->setTitle('Nouvelle')->setDescription('Une chasse')->setDifficulty(1)->setDesignerTeam($team);

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/create', $this->payload($I, $hunt, [$mcq], ['hunt_types' => json_encode([$huntType->getId()]), 'action' => 'publish']));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseJsonMatchesJsonPath('$.url');
        $I->seeInRepository(TreasureHunt::class, ['title' => 'Nouvelle', 'owner' => $owner->getId(), 'status' => TreasureHunt::STATE_OPENED, 'riddleCount' => 1]);
        $I->seeInRepository(MCQRiddle::class, ['title' => 'Capitale', 'maxScoringAttempts' => 2, 'revealAnswerCount' => false]);
    }

    public function anInvalidRiddleIsRejectedWithItsMessage(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles(TreasureHunt::STATE_DRAFT);
        $broken = ['type' => 'mcq', 'title' => 'Cassée', 'description' => 'Énoncé', 'difficulty' => 1, 'maxScoringAttempts' => 3,
            'choices' => ['a', 'b'], 'answers' => ['z']];

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, [...$riddles, $broken]));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseContainsJson(['violations' => ['La bonne réponse "z" ne figure pas parmi les choix proposés.']]);
        $I->dontSeeInRepository(Riddle::class, ['title' => 'Cassée']);
    }

    public function pagesRenderForTheOwnerAndExposeWorkflowActions(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt] = $this->huntWithTwoRiddles();

        // 2. 'Act' / 3. 'Assert' : liste, détails, édition
        $I->amLoggedInAs($owner, 'main');
        $I->sendGet('/designer/hunt');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContains('Vieux Reims');

        $I->sendGet('/designer/hunt/'.$hunt->getId().'/details');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContains('Première');
        $I->seeResponseContains('/designer/hunt/'.$hunt->getId().'/transition/close');
        $I->dontSeeResponseContains('data-riddle-data'); // lecture seule : rien à rejouer côté client

        $I->sendGet('/designer/hunt/'.$hunt->getId().'/edit');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContains('data-riddle-data');
    }

    public function closingAndReopeningFollowTheWorkflow(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt] = $this->huntWithTwoRiddles();
        $I->amLoggedInAs($owner, 'main');
        $token = $this->csrfToken($I);

        // 2. 'Act' / 3. 'Assert' : fermer, puis rouvrir, puis une transition impossible
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/transition/close', ['_token' => $token]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInRepository(TreasureHunt::class, ['id' => $hunt->getId(), 'status' => TreasureHunt::STATE_CLOSED]);

        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/transition/republish', ['_token' => $token]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInRepository(TreasureHunt::class, ['id' => $hunt->getId(), 'status' => TreasureHunt::STATE_OPENED]);

        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/transition/republish', ['_token' => $token]);
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
    }

    public function aRequestWithoutCsrfTokenIsRefused(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, $riddles, ['_token' => 'forged']));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function aStrangerCannotEditTheHunt(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();
        $stranger = UserFactory::createOne()->_real();

        // 2. 'Act'
        $I->amLoggedInAs($stranger, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, $riddles));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }
}
