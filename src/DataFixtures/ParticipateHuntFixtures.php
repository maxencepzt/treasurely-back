<?php

namespace App\DataFixtures;

use App\Factory\ParticipateHuntFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ParticipateHuntFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        ParticipateHuntFactory::createMany(10);
    }

    /**
     * @return class-string<FixtureInterface>[]
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            TreasureHuntFixtures::class,
            RiddleFixtures::class,
            ParticipateRiddleFixtures::class,
        ];
    }
}
