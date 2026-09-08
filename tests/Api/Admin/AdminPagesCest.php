<?php

declare(strict_types=1);

namespace App\Tests\Api\Admin;

use App\Tests\Support\ApiTester;
use Codeception\Attribute\Examples;
use Codeception\Example;
use Codeception\Util\HttpCode;

/**
 * Chaque CRUD rend sa liste, son détail et son formulaire d'édition ; la page de création est
 * ouverte ou fermée selon la règle du back office (l'application crée les chasses, les énigmes
 * et les participations, l'admin les corrige).
 */
final class AdminPagesCest
{
    use AdminWorld;

    /**
     * @param Example<int, string|int> $example
     */
    #[Examples('treasure-hunt', 'hunt', 'Chasses au trésor', 'Modifier la chasse au trésor', HttpCode::FORBIDDEN)]
    #[Examples('hunt-type', 'huntType', 'Types de chasse', 'Modifier le type de chasse', HttpCode::OK)]
    #[Examples('participate-hunt', 'participation', 'Participations aux chasses', 'Modifier la participation', HttpCode::FORBIDDEN)]
    #[Examples('riddle', 'text', 'Énigmes', "Modifier l'énigme", HttpCode::FORBIDDEN)]
    #[Examples('g-p-s-riddle', 'gps', 'Énigmes GPS', "Modifier l'énigme GPS", HttpCode::FORBIDDEN)]
    #[Examples('m-c-q-riddle', 'mcq', 'Énigmes QCM', "Modifier l'énigme QCM", HttpCode::FORBIDDEN)]
    #[Examples('q-r-riddle', 'qr', 'Énigmes QR', "Modifier l'énigme QR", HttpCode::FORBIDDEN)]
    #[Examples('text-riddle', 'text', 'Énigmes texte', "Modifier l'énigme texte", HttpCode::FORBIDDEN)]
    #[Examples('participate-riddle', 'participateRiddle', 'Participations aux énigmes', 'Modifier la participation', HttpCode::FORBIDDEN)]
    #[Examples('user', 'player', 'Utilisateurs', "Modifier l'utilisateur", HttpCode::OK)]
    #[Examples('team', 'playerTeam', 'Équipes', "Modifier l'équipe", HttpCode::FORBIDDEN)]
    #[Examples('team-join-request', 'joinRequest', "Demandes d'adhésion", 'Modifier la demande', HttpCode::FORBIDDEN)]
    #[Examples('picture', 'picture', 'Images', "Modifier l'image", HttpCode::OK)]
    public function everyPageOfTheCrudRenders(ApiTester $I, Example $example): void
    {
        // 1. 'Arrange'
        [$slug, $key, $plural, $editTitle, $newCode] = [$example[0], $example[1], $example[2], $example[3], $example[4]];
        $world = $this->world($I);
        $id = $world[$key]->getId();
        $I->amLoggedInAs($world['admin'], 'main');

        // 2. 'Act' + 3. 'Assert', page par page
        $I->sendGet('/admin/'.$slug);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertStringContainsString($plural, $this->page($I));

        $I->sendGet('/admin/'.$slug.'/'.$id);
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->sendGet('/admin/'.$slug.'/'.$id.'/edit');
        $I->seeResponseCodeIs(HttpCode::OK);
        $edit = $this->page($I);
        $I->assertStringContainsString($editTitle, $edit);
        $I->assertSame(1, substr_count($edit, 'type="submit"'), 'un seul bouton d\'enregistrement sur la page de modification');

        $I->sendGet('/admin/'.$slug.'/new');
        $I->seeResponseCodeIs($newCode);
        if (HttpCode::OK === $newCode) {
            $I->assertSame(1, substr_count($this->page($I), 'type="submit"'), 'un seul bouton d\'enregistrement sur la page de création');
        }
    }

    public function theTeamListShowsTheTypeInFrench(ApiTester $I): void
    {
        // 1. 'Arrange'
        $world = $this->world($I);
        $I->amLoggedInAs($world['admin'], 'main');

        // 2. 'Act'
        $I->sendGet('/admin/team');

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::OK);
        $page = $this->page($I);
        $I->assertStringContainsString('Joueurs', $page);
        $I->assertStringContainsString('Concepteurs', $page);
    }

    /** Le corps HTML, apostrophes et accents décodés pour comparer avec les libellés source. */
    private function page(ApiTester $I): string
    {
        return html_entity_decode((string) $I->grabResponse(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
