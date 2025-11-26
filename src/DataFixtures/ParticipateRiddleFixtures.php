<?php

namespace App\DataFixtures;

use App\Factory\ParticipateRiddleFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ParticipateRiddleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        ParticipateRiddleFactory::createMany(50);
    }

    /**
     * @return class-string<FixtureInterface>[]
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            RiddleFixtures::class,
            TreasureHuntFixtures::class,
        ];
    }
}
