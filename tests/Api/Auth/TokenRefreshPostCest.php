<?php

declare(strict_types=1);

namespace App\Tests\Api\Auth;

use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

/**
 * POST /api/token/refresh et POST /api/token/invalidate (gesdinet/jwt-refresh-token-bundle).
 */
final class TokenRefreshPostCest
{
    public function canExchangeARefreshTokenForANewAccessToken(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne(['nickname' => 'chasseur', 'password' => 'Secret123!'])->_real();
        $refreshToken = $this->login($I);

        // 2. 'Act'
        $I->sendPost('/api/token/refresh', ['refresh_token' => $refreshToken]);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(['token' => 'string', 'refresh_token' => 'string']);
        $I->amBearerAuthenticated($I->grabDataFromResponseByJsonPath('$.token')[0]);
        $I->sendGet('/api/me');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['id' => $user->getId()]);
    }

    public function anUnknownRefreshTokenIsRejected(ApiTester $I): void
    {
        // 1. 'Arrange' aucun jeton émis

        // 2. 'Act'
        $I->sendPost('/api/token/refresh', ['refresh_token' => 'inconnu']);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->dontSeeResponseJsonMatchesJsonPath('$.token');
    }

    public function anInvalidatedRefreshTokenIsRejected(ApiTester $I): void
    {
        // 1. 'Arrange'
        UserFactory::createOne(['nickname' => 'chasseur', 'password' => 'Secret123!']);
        $refreshToken = $this->login($I);

        // 2. 'Act'
        $I->sendPost('/api/token/invalidate', ['refresh_token' => $refreshToken]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->sendPost('/api/token/refresh', ['refresh_token' => $refreshToken]);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->dontSeeResponseJsonMatchesJsonPath('$.token');
    }

    /**
     * Connecte « chasseur » et rend son refresh token.
     */
    private function login(ApiTester $I): string
    {
        $I->sendPost('/api/auth', ['nickname' => 'chasseur', 'password' => 'Secret123!']);
        $I->seeResponseCodeIs(HttpCode::OK);

        return $I->grabDataFromResponseByJsonPath('$.refresh_token')[0];
    }
}
