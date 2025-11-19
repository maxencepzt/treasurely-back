<?php

namespace App\DataFixtures;

use App\Factory\ParticipateHuntFactory;
use App\Factory\PlayerTeamFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ParticipateHuntFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        ParticipateHuntFactory::createMany(10, function () {
            $playerTeam = PlayerTeamFactory::random();

            return [
                'playerTeam' => 1 == random_int(0, 1) ? $playerTeam : null,
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
