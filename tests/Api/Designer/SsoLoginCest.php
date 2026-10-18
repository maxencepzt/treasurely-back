<?php

declare(strict_types=1);

namespace App\Tests\Api\Designer;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

/**
 * POST /sso/login : l'échange du JWT de l'application contre une session de la façade.
 */
final class SsoLoginCest
{
    private const string SESSION_COOKIE = 'MOCKSESSID'; // nom de la session de test (mock_file)

    /**
     * Un cookie de session posé d'avance, par exemple depuis un autre sous-domaine de
     * maxencepzt.fr, ne doit pas devenir la session connectée (fixation de session).
     */
    public function theSessionIdChangesAtLogin(ApiTester $I): void
    {
        // 1. 'Arrange' : une session ouverte avant la connexion
        $user = UserFactory::createOne()->_real();
        $jwt = $I->grabService(JWTTokenManagerInterface::class)->create($user);
        $I->sendGet('/designer/dashboard');
        $before = $I->grabCookie(self::SESSION_COOKIE);
        $I->assertNotEmpty($before);

        // 2. 'Act'
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->send('POST', '/sso/login', (string) json_encode(['token' => $jwt]));

        // 3. 'Assert'
        $after = $I->grabCookie(self::SESSION_COOKIE);
        $I->assertNotEquals($before, $after);
        $I->seeInRepository(User::class, ['id' => $user->getId(), 'symfonySessionId' => $after]);
    }

    /** Un formulaire d'un autre site n'envoie pas de JSON : il ne doit connecter personne. */
    public function aFormPostIsRefused(ApiTester $I): void
    {
        // 1. 'Arrange'
        $user = UserFactory::createOne()->_real();
        $jwt = $I->grabService(JWTTokenManagerInterface::class)->create($user);

        // 2. 'Act'
        $I->haveHttpHeader('Content-Type', 'text/plain');
        $I->send('POST', '/sso/login', (string) json_encode(['token' => $jwt]));

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::UNSUPPORTED_MEDIA_TYPE);
        $I->seeInRepository(User::class, ['id' => $user->getId(), 'symfonySessionId' => null]);
    }
}
