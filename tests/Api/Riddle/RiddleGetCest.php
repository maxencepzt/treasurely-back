<?php

declare(strict_types=1);

namespace App\Tests\Api\Riddle;

use App\Entity\GPSRiddle;
use App\Entity\MCQRiddle;
use App\Entity\QRRiddle;
use App\Entity\Riddle;
use App\Entity\TextRiddle;
use App\Factory\GPSRiddleFactory;
use App\Factory\MCQRiddleFactory;
use App\Factory\QRRiddleFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Examples;
use Codeception\Example;
use Codeception\Util\HttpCode;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RiddleGetCest
{
    public function mcqRiddleAnnouncesItsTypeAndExpectedAnswerCount(ApiTester $I): void
    {
        // 1. 'Arrange'
        $riddle = MCQRiddleFactory::createOne([
            'choices' => ['Paris', 'Lyon', 'Reims', 'Nantes'],
            'answers' => ['Paris', 'Reims'],
            'revealAnswerCount' => true,
        ])->_real();

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/riddles/'.$riddle->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsAnEntity(Riddle::class, '/api/riddles/'.$riddle->getId());
        $I->seeResponseContainsJson([
            'type' => 'mcq',
            'expectedAnswerCount' => 2,
            'maxScoringAttempts' => Riddle::DEFAULT_MAX_SCORING_ATTEMPTS,
            'choices' => ['Paris', 'Lyon', 'Reims', 'Nantes'],
        ]);
        $I->dontSeeResponseJsonMatchesJsonPath('$.answers');
    }

    public function expectedAnswerCountIsWithheldWhenTheDesignerSaysSo(ApiTester $I): void
    {
        // 1. 'Arrange'
        $riddle = MCQRiddleFactory::createOne([
            'choices' => ['Paris', 'Lyon', 'Reims', 'Nantes'],
            'answers' => ['Paris', 'Reims'],
            'revealAnswerCount' => false,
        ])->_real();

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/riddles/'.$riddle->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson(['type' => 'mcq']);
        // API Platform omet les valeurs nulles : le nombre non communiqué est une clé absente.
        $I->dontSeeResponseJsonMatchesJsonPath('$.expectedAnswerCount');
        $I->dontSeeResponseJsonMatchesJsonPath('$.revealAnswerCount');
    }

    /**
     * @param Example<int, string> $example
     */
    #[Examples('text')]
    #[Examples('gps')]
    #[Examples('qr')]
    public function everySubtypeAnnouncesItsType(ApiTester $I, Example $example): void
    {
        // 1. 'Arrange'
        $type = $example[0];
        $riddle = (match ($type) {
            'text' => TextRiddleFactory::createOne(),
            'gps' => GPSRiddleFactory::createOne(),
            'qr' => QRRiddleFactory::createOne(),
            default => throw new \InvalidArgumentException(sprintf('Type d\'énigme inconnu : %s', $type)),
        })->_real();

        // 2. 'Act'
        $I->amLoggedInAs(UserFactory::createOne()->_real());
        $I->sendGet('/api/riddles/'.$riddle->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson([
            'type' => $type,
            'maxScoringAttempts' => Riddle::DEFAULT_MAX_SCORING_ATTEMPTS,
        ]);
        $I->dontSeeResponseJsonMatchesJsonPath('$.expectedAnswerCount');
        // Les solutions ne sont jamais publiées, quel que soit le type.
        foreach (['answer', 'code', 'latitude', 'longitude'] as $solution) {
            $I->dontSeeResponseJsonMatchesJsonPath('$.'.$solution);
        }
    }

    public function cannotReadRiddleAsGuest(ApiTester $I): void
    {
        // 1. 'Arrange'
        $riddle = TextRiddleFactory::createOne()->_real();

        // 2. 'Act'
        $I->sendGet('/api/riddles/'.$riddle->getId());

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function mcqRiddleRejectsAnAnswerAbsentFromItsChoices(ApiTester $I): void
    {
        // 1. 'Arrange'
        $riddle = (new MCQRiddle())
            ->setTitle('Capitale')
            ->setDescription('Quelle est la capitale de la France ?')
            ->setDifficulty(1)
            ->setOrderNumber(1)
            ->setChoices(['Paris', 'Lyon'])
            ->setAnswers(['Marseille']);

        // 2. 'Act'
        $violations = $I->grabService(ValidatorInterface::class)->validate($riddle);

        // 3. 'Assert'
        $I->assertCount(1, $violations);
        $I->assertSame('answers', $violations[0]->getPropertyPath());
    }

    public function mcqRiddleNeedsTwoChoicesAndOneAnswer(ApiTester $I): void
    {
        // 1. 'Arrange'
        $riddle = (new MCQRiddle())
            ->setTitle('Vide')
            ->setDescription('Un QCM sans contenu')
            ->setDifficulty(1)
            ->setOrderNumber(1)
            ->setChoices(['Seul'])
            ->setAnswers([]);

        // 2. 'Act'
        $violations = $I->grabService(ValidatorInterface::class)->validate($riddle);

        // 3. 'Assert'
        $paths = [];
        foreach ($violations as $violation) {
            $paths[] = $violation->getPropertyPath();
        }
        sort($paths);
        $I->assertSame(['answers', 'choices'], $paths);
    }

    public function scoringAttemptsAreBoundedForEveryType(ApiTester $I): void
    {
        // 1. 'Arrange'
        $riddle = (new GPSRiddle())
            ->setTitle('Cathédrale')
            ->setDescription('Rendez-vous devant le portail.')
            ->setDifficulty(2)
            ->setOrderNumber(1)
            ->setLatitude(49.2537)
            ->setLongitude(4.0340)
            ->setMaxScoringAttempts(Riddle::MAX_SCORING_ATTEMPTS + 1);

        // 2. 'Act'
        $violations = $I->grabService(ValidatorInterface::class)->validate($riddle);

        // 3. 'Assert'
        $I->assertCount(1, $violations);
        $I->assertSame('maxScoringAttempts', $violations[0]->getPropertyPath());
    }

    public function textFieldsAreBoundedToTheirColumnLength(ApiTester $I): void
    {
        // 1. 'Arrange' : titre de 21 caractères pour une colonne de 20, réponse vide
        $riddle = (new TextRiddle())
            ->setTitle(str_repeat('a', 21))
            ->setDescription('Énoncé')
            ->setDifficulty(1)
            ->setOrderNumber(1)
            ->setAnswer('');

        // 2. 'Act'
        $violations = $I->grabService(ValidatorInterface::class)->validate($riddle);

        // 3. 'Assert'
        $paths = [];
        foreach ($violations as $violation) {
            $paths[] = $violation->getPropertyPath();
        }
        sort($paths);
        $I->assertSame(['answer', 'title'], $paths);
    }

    /**
     * @param Example<int, mixed> $example
     */
    #[Examples('CODE1', 1)]
    #[Examples('treasurely_12345678', 1)]
    #[Examples('treasurely_1234567890', 1)]
    #[Examples('treasurely_123456789', 0)]
    public function qrCodesFollowTheImposedFormat(ApiTester $I, Example $example): void
    {
        // 1. 'Arrange'
        $riddle = (new QRRiddle())
            ->setTitle('Scan')
            ->setDescription('Trouvez le QR.')
            ->setDifficulty(1)
            ->setOrderNumber(1)
            ->setCode($example[0]);

        // 2. 'Act'
        $violations = $I->grabService(ValidatorInterface::class)->validate($riddle);

        // 3. 'Assert'
        $I->assertCount($example[1], $violations);
        $I->assertMatchesRegularExpression(QRRiddle::CODE_PATTERN, QRRiddle::generateCode());
    }

    public function gpsCoordinatesMustBeOnEarth(ApiTester $I): void
    {
        // 1. 'Arrange'
        $riddle = (new GPSRiddle())
            ->setTitle('Nulle part')
            ->setDescription('Coordonnées impossibles.')
            ->setDifficulty(2)
            ->setOrderNumber(1)
            ->setLatitude(1231327.84573)
            ->setLongitude(4.0340);

        // 2. 'Act'
        $violations = $I->grabService(ValidatorInterface::class)->validate($riddle);

        // 3. 'Assert'
        $I->assertCount(1, $violations);
        $I->assertSame('latitude', $violations[0]->getPropertyPath());
    }
}
