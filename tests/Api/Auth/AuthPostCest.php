<?php

declare(strict_types=1);

namespace App\Tests\Api\Auth;

use App\Enum\Gender;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Examples;
use Codeception\Example;
use Codeception\Util\HttpCode;

/**
 * POST /api/auth : la connexion par pseudo et mot de passe (json_login + Lexik).
 *
 * La suite dispose de sa propre paire de clés JWT (.env.test), ces tests parcourent donc la
 * chaîne réelle : mot de passe vérifié, jeton signé, jeton accepté par la requête suivante.
 */
final class AuthPostCest
{
    public function canLoginWithNicknameAndPassword(ApiTester $I): void
    {
        // 1. 'Arrange'
        UserFactory::createOne(['nickname' => 'chasseur', 'password' => 'Secret123!']);

        // 2. 'Act'
        $I->sendPost('/api/auth', ['nickname' => 'chasseur', 'password' => 'Secret123!']);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType(['token' => 'string', 'refresh_token' => 'string']);
    }

    public function theTokenAuthenticatesTheNextRequest(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne(['nickname' => 'chasseur', 'password' => 'Secret123!'])->_real();
        $I->sendPost('/api/auth', ['nickname' => 'chasseur', 'password' => 'Secret123!']);
        $I->seeResponseCodeIs(HttpCode::OK);

        // 2. 'Act'
        $I->amBearerAuthenticated($I->grabDataFromResponseByJsonPath('$.token')[0]);
        $I->sendGet('/api/me');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['id' => $user->getId(), 'nickname' => 'chasseur']);
    }

    /**
     * Régression : POST /register stockait le mot de passe en clair et le compte créé ne
     * pouvait jamais se connecter (corrigé par UserPasswordProcessor).
     */
    public function aRegisteredUserCanLogin(ApiTester $I): void
    {
        // 1. 'Arrange'
        $I->sendPost('/api/register', [
            'nickname' => 'inscrit',
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'inscrit@example.com',
            'birthDate' => '1995-06-15',
            'phone' => '0123456789',
            'plainPassword' => 'Secret123!',
            'gender' => Gender::MAN->value,
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);

        // 2. 'Act'
        $I->sendPost('/api/auth', ['nickname' => 'inscrit', 'password' => 'Secret123!']);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseJsonMatchesJsonPath('$.token');
    }

    /**
     * @param Example<int, string> $example
     */
    #[Examples('chasseur', 'mauvais')]
    #[Examples('inconnu', 'Secret123!')]
    public function cannotLoginWithWrongCredentials(ApiTester $I, Example $example): void
    {
        // 1. 'Arrange'
        UserFactory::createOne(['nickname' => 'chasseur', 'password' => 'Secret123!']);

        // 2. 'Act'
        $I->sendPost('/api/auth', ['nickname' => $example[0], 'password' => $example[1]]);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseContainsJson(['code' => 401, 'message' => 'Identifiants invalides.']);
        $I->dontSeeResponseJsonMatchesJsonPath('$.token');
    }

    public function aForgedTokenIsRejected(ApiTester $I): void
    {
        // 1. 'Arrange'
        $I->amBearerAuthenticated('pas.un.jwt');

        // 2. 'Act'
        $I->sendGet('/api/me');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseContainsJson(['code' => 401, 'message' => 'Invalid JWT Token']);
    }
}
