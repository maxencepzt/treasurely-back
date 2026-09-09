<?php

declare(strict_types=1);

namespace App\Tests\Api\Fixtures;

use App\DataFixtures\DemoFixtures;
use App\Entity\QRRiddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Le jeu de démonstration se charge sur une base vide et donne ce qu'un joueur verra en production. */
final class DemoFixturesCest
{
    public function loadsAnAdminFourPlayersAndTwoOpenedParisHunts(ApiTester $I): void
    {
        $em = $I->grabService(EntityManagerInterface::class);
        $I->grabService(DemoFixtures::class)->load($em);

        $admin = $em->getRepository(User::class)->findOneBy(['nickname' => 'maxence']);
        $I->assertContains('ROLE_ADMIN', $admin->getRoles());
        $I->assertTrue($I->grabService(UserPasswordHasherInterface::class)->isPasswordValid($admin, DemoFixtures::PASSWORD));
        $I->assertCount(5, $em->getRepository(User::class)->findAll());

        foreach ($em->getRepository(TreasureHunt::class)->findAll() as $hunt) {
            $I->assertSame('opened', $hunt->getStatus());
            $I->assertSame($hunt->getRiddles()->count(), $hunt->getRiddleCount());
            foreach ($hunt->getRiddles() as $riddle) {
                if ($riddle instanceof QRRiddle) {
                    $I->assertMatchesRegularExpression(QRRiddle::CODE_PATTERN, $riddle->getCode());
                }
            }
        }

        $I->amLoggedInAs($em->getRepository(User::class)->findOneBy(['nickname' => 'camille']));
        $I->sendGet('/api/treasure_hunts');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['totalItems' => 2]);
        $I->seeResponseContainsJson(['member' => [['title' => 'Secrets de la Cité', 'riddleCount' => 5, 'location' => 'Paris']]]);
        $I->seeResponseContainsJson(['member' => [['title' => 'La Butte Montmartre', 'riddleCount' => 6, 'difficulty' => 3]]]);
    }
}
