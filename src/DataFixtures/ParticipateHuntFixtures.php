<?php

namespace App\DataFixtures;

use App\Factory\ParticipateHuntFactory;
use App\Factory\PlayerTeamFactory;
use App\Factory\TreasureHuntFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ParticipateHuntFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        ParticipateHuntFactory::createMany(50, function () {
            $playerTeam = PlayerTeamFactory::random();
            $hunt = TreasureHuntFactory::random();
            $currentRiddle = $hunt->getRiddles()->first();

            return [
                'playerTeam' => 1 == random_int(0, 1) ? $playerTeam : null,
                'hunt' => $hunt,
                'currentRiddle' => $currentRiddle,
            ];
        });
    }

    /**
     * @return class-string<FixtureInterface>[]
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            TreasureHuntFixtures::class,
        ];
    }
}
