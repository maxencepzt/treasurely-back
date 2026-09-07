<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\Support\ApiTester;
use Codeception\Attribute\Examples;
use Codeception\Example;
use Codeception\Util\HttpCode;

/**
 * Les trois formes de la documentation. La forme Hydra échouait sur les relations typées
 * avec un sous-type d'équipe, qui ne déclare aucune opération.
 */
final class DocsCest
{
    /**
     * @param Example<int, string> $example
     */
    #[Examples('application/ld+json', 'ApiDocumentation')]
    #[Examples('application/vnd.openapi+json', '"openapi"')]
    #[Examples('text/html', 'swagger-ui')]
    public function everyDocumentationFormatRenders(ApiTester $I, Example $example): void
    {
        $I->haveHttpHeader('Accept', $example[0]);
        $I->sendGet('/api/docs');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContains($example[1]);
    }

    public function theHydraVocabularyDocumentsTeamRelationsAsTeams(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/ld+json');
        $I->sendGet('/api/docs');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContains('"#Team"');
        $I->dontSeeResponseContains('"#PlayerTeam"');
    }
}
