<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

final class TrustedProxyCest
{
    public function absoluteUrlsFollowTheForwardedProtocol(ApiTester $I): void
    {
        // 1. 'Arrange' : le proxy annonce une requête https
        $I->stopFollowingRedirects();
        $I->haveHttpHeader('X-Forwarded-Proto', 'https');

        // 2. 'Act' : la redirection du slash final est construite depuis la requête
        $I->sendGet('/sso/redirect/front/');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::MOVED_PERMANENTLY);
        $I->seeHttpHeader('Location', 'https://localhost/sso/redirect/front');
    }
}
