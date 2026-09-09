<?php

declare(strict_types=1);

namespace App\Tests\Api\Designer;

use App\Entity\MCQRiddle;
use App\Entity\ParticipateHunt;
use App\Entity\ParticipateRiddle;
use App\Entity\QRRiddle;
use App\Entity\Riddle;
use App\Entity\TextRiddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Factory\DesignerTeamFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\MCQRiddleFactory;
use App\Factory\QRRiddleFactory;
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
        $team = DesignerTeamFactory::createOne(['owner' => $owner])->_real(); // le propriétaire en devient membre
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
     * Ajoute un membre et l'écrit tout de suite. Les factories Foundry rafraîchissent
     * leurs proxies depuis la base avant chaque appel (TreasureHuntFactory::defaults()
     * lit les membres d'une équipe tirée au hasard) : une adhésion encore en attente
     * de flush serait silencieusement perdue.
     */
    private function join(ApiTester $I, TreasureHunt $hunt): User
    {
        $member = UserFactory::createOne()->_real();
        $hunt->getDesignerTeam()?->addMember($member);
        $I->flushToDatabase();

        return $member;
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

    public function creatingAHuntWithAnImagePersistsIt(ApiTester $I): void
    {
        // 1. 'Arrange' : un PNG d'un pixel ; l'envoi d'une image flushe avant que les énigmes soient posées
        $owner = UserFactory::createOne()->_real();
        $team = DesignerTeamFactory::createOne(['owner' => $owner])->_real();
        $hunt = (new TreasureHunt())->setTitle('Illustrée')->setDescription('Avec une image')->setDifficulty(1)->setDesignerTeam($team);
        $png = tempnam(sys_get_temp_dir(), 'treasurely-');
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/create', $this->payload($I, $hunt, [$this->newTextRiddle('Une seule')], ['action' => 'publish']), ['image' => $png]);
        unlink($png);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeInRepository(TreasureHunt::class, ['title' => 'Illustrée', 'status' => TreasureHunt::STATE_OPENED, 'riddleCount' => 1]);
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

    public function reattachingTheHuntToAForeignTeamIsRefused(ApiTester $I): void
    {
        // 1. 'Arrange' : l'équipe étrangère est créée avant le propriétaire de la chasse,
        // sinon la factory, qui recrute des membres parmi les utilisateurs existants, pourrait l'y inclure
        $foreignTeam = DesignerTeamFactory::createOne(['owner' => UserFactory::createOne()])->_real();
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();
        $originalTeamId = $hunt->getDesignerTeam()?->getId();
        $I->assertFalse($foreignTeam->hasMember($owner), 'Le montage du test est faux : le propriétaire est membre de l\'équipe étrangère.');

        // 2. 'Act' : jeton valide, seule l'équipe est en cause
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, $riddles, ['designer_team_id' => $foreignTeam->getId()]));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeInRepository(TreasureHunt::class, ['id' => $hunt->getId(), 'designerTeam' => $originalTeamId]);
    }

    public function anUnknownTeamIsAValidationError(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, $riddles, ['designer_team_id' => 999999]));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseContainsJson(['violations' => ['L\'équipe choisie n\'existe pas.']]);
    }

    public function aFileThatIsNotAnImageIsRefused(ApiTester $I): void
    {
        // 1. 'Arrange' : un fichier texte présenté comme image
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();
        $notAnImage = tempnam(sys_get_temp_dir(), 'treasurely-');
        file_put_contents($notAnImage, 'ceci n\'est pas une image');

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, $riddles, ['name' => 'Avec image']), ['image' => $notAnImage]);
        unlink($notAnImage);

        // 3. 'Assert' : refus motivé, et rien d'autre n'a été enregistré
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->assertStringContainsString('Le type de fichier n\'est pas autorisé', (string) $I->grabDataFromResponseByJsonPath('$.error')[0]);
        $I->seeInRepository(TreasureHunt::class, ['id' => $hunt->getId(), 'title' => 'Vieux Reims']);
    }

    public function huntTypesFollowTheSubmittedSelection(ApiTester $I): void
    {
        // 1. 'Arrange' : la chasse porte le type A, le formulaire ne coche que le type B
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();
        $typeA = HuntTypeFactory::createOne(['title' => 'Parcours nocturne'])->_real();
        $typeB = HuntTypeFactory::createOne(['title' => 'Parcours urbain'])->_real();
        foreach ($hunt->getHuntType() as $current) {
            $hunt->removeHuntType($current);
        }
        $hunt->addHuntType($typeA);
        $I->flushToDatabase();

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, $riddles, ['hunt_types' => json_encode([$typeB->getId()])]));

        // 3. 'Assert' : la page de détails reflète la sélection
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->sendGet('/designer/hunt/'.$hunt->getId().'/details');
        $I->seeResponseContains('Parcours urbain');
        $I->dontSeeResponseContains('Parcours nocturne');
    }

    public function aTeamMemberSeesOpenedHuntsButNotDrafts(ApiTester $I): void
    {
        // 1. 'Arrange' : un membre de l'équipe qui n'est ni propriétaire de la chasse ni de l'équipe
        ['owner' => $owner, 'hunt' => $opened] = $this->huntWithTwoRiddles();
        $member = $this->join($I, $opened);
        $draft = TreasureHuntFactory::createOne(['owner' => $owner, 'designerTeam' => $opened->getDesignerTeam(), 'status' => TreasureHunt::STATE_DRAFT])->_real();

        // 2. 'Act' / 3. 'Assert'
        $I->amLoggedInAs($member, 'main');
        $I->sendGet('/designer/hunt/'.$opened->getId().'/details');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->dontSeeResponseContains('/designer/hunt/'.$opened->getId().'/edit'); // voir, pas modifier

        $I->sendGet('/designer/hunt/'.$draft->getId().'/details');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function transitionsRequireTheTokenAndTheEditRight(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt] = $this->huntWithTwoRiddles();
        $member = $this->join($I, $hunt);

        // 2. 'Act' / 3. 'Assert' : propriétaire sans jeton
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/transition/close', []);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

        // membre avec un jeton valide, mais sans droit d'édition : il voit la chasse,
        // ce qui prouve qu'il est bien membre et non un inconnu, mais ne la ferme pas
        $I->amLoggedInAs($member, 'main');
        $I->sendGet('/designer/hunt/'.$hunt->getId().'/details');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/transition/close', ['_token' => $this->csrfToken($I)]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

        $I->seeInRepository(TreasureHunt::class, ['id' => $hunt->getId(), 'status' => TreasureHunt::STATE_OPENED]);
    }

    public function aMalformedPayloadIsRejectedBeforeAnythingIsBuilt(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();

        // 2. 'Act' : nom vide et type d'énigme inconnu dans la même charge utile
        $I->amLoggedInAs($owner, 'main');
        $unknown = ['type' => 'video', 'title' => 'Clip', 'description' => 'Énoncé', 'difficulty' => 1, 'maxScoringAttempts' => 3];
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, [...$riddles, $unknown], ['name' => '   ']));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseContains('Le nom de la chasse est requis.');
        $I->seeInRepository(TreasureHunt::class, ['id' => $hunt->getId(), 'title' => 'Vieux Reims']);
        $I->dontSeeInRepository(Riddle::class, ['title' => 'Clip']);
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

    public function aQrRiddleReceivesACodeThatNoOneChooses(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt] = $this->huntWithTwoRiddles();
        $chosen = ['type' => 'qr', 'title' => 'Scan', 'description' => 'Trouvez le QR.', 'difficulty' => 1, 'maxScoringAttempts' => 3, 'code' => 'CHOISI'];

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/create', $this->payload($I, $hunt, [$chosen]));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $riddle = $I->grabEntityFromRepository(QRRiddle::class, ['title' => 'Scan']);
        $I->assertMatchesRegularExpression(QRRiddle::CODE_PATTERN, $riddle->getCode());
        $I->assertNotSame('CHOISI', $riddle->getCode());
    }

    public function theQrCodeSurvivesAnEdit(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();
        $qr = QRRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 3, 'title' => 'Scan', 'code' => 'treasurely_000000001'])->_real();
        $edited = $this->describe($qr) + ['code' => 'treasurely_999999999'];
        $edited['title'] = 'Scan modifié';

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendFormPost('/designer/hunt/'.$hunt->getId().'/edit', $this->payload($I, $hunt, [$riddles[0], $riddles[1], $edited]));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInRepository(QRRiddle::class, ['id' => $qr->getId(), 'title' => 'Scan modifié', 'code' => 'treasurely_000000001']);
    }

    public function theQrImageIsServedToTheDesigner(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt] = $this->huntWithTwoRiddles();
        $qr = QRRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 3, 'code' => 'treasurely_000000001'])->_real();

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendGet('/designer/hunt/'.$hunt->getId().'/riddle/'.$qr->getId().'/qr.svg');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeHttpHeader('Content-Type', 'image/svg+xml');
        $I->seeResponseContains('<svg');
    }

    public function theQrImageIsNotServedToStrangers(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['hunt' => $hunt] = $this->huntWithTwoRiddles();
        $qr = QRRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 3])->_real();

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real(), 'main');
        $I->sendGet('/designer/hunt/'.$hunt->getId().'/riddle/'.$qr->getId().'/qr.svg');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function theQrImageOfAnotherHuntIsNotFound(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();
        $elsewhere = QRRiddleFactory::createOne(['orderNumber' => 1])->_real();

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendGet('/designer/hunt/'.$hunt->getId().'/riddle/'.$elsewhere->getId().'/qr.svg');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        // Une énigme de la chasse qui n'est pas un QR n'a pas d'image non plus
        $I->assertNotInstanceOf(QRRiddle::class, $riddles[0]);
    }

    private function participation(ApiTester $I, TreasureHunt $hunt, Riddle $riddle, string $nickname, bool $finished, int $score = 0, int $time = 0): ParticipateHunt
    {
        $participation = (new ParticipateHunt())
            ->setHunter(UserFactory::createOne(['nickname' => $nickname])->_real())
            ->setHunt($hunt)
            ->setCurrentRiddle($riddle)
            ->setFinished($finished)
            ->setScore($score)
            ->setTime($time)
            ->setLastParticipate(new \DateTimeImmutable());
        $I->haveInRepository($participation);

        return $participation;
    }

    public function theDetailsPageReportsParticipationsAndTheRanking(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();
        $this->participation($I, $hunt, $riddles[1], 'beta-finisseur', true, 1000, 300);
        $this->participation($I, $hunt, $riddles[1], 'alpha-finisseur', true, 3000, 100);
        $this->participation($I, $hunt, $riddles[0], 'gamma-en-cours', false);

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendGet('/designer/hunt/'.$hunt->getId().'/details');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContains('data-stat="participations">3<');
        $I->seeResponseContains('data-stat="finished">2<');
        $I->seeResponseContains('data-stat="average-score">2000<');
        $page = (string) $I->grabResponse();
        $I->assertLessThan(strpos($page, 'beta-finisseur'), strpos($page, 'alpha-finisseur'), 'Le meilleur score est classé premier');
        $I->dontSeeResponseContains('gamma-en-cours');
    }

    public function theHuntListCountsParticipations(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();
        foreach (['un', 'deux', 'trois'] as $suffix) {
            $this->participation($I, $hunt, $riddles[0], 'joueur-'.$suffix, false);
        }

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendGet('/designer/hunt');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContains('3 participations');
    }

    public function theDashboardCountsParticipationsOfTheOwnedTeams(ApiTester $I): void
    {
        // 1. 'Arrange'
        ['owner' => $owner, 'hunt' => $hunt, 'riddles' => $riddles] = $this->huntWithTwoRiddles();
        $this->participation($I, $hunt, $riddles[0], 'inscrit-un', false);
        $this->participation($I, $hunt, $riddles[1], 'inscrit-deux', true, 500, 60);

        // 2. 'Act'
        $I->amLoggedInAs($owner, 'main');
        $I->sendGet('/designer/dashboard');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContains('data-stat="participations">2<');
    }
}
