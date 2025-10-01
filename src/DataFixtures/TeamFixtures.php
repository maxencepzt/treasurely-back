<?php

namespace App\DataFixtures;

use App\Factory\TeamFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TeamFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        TeamFactory::createMany(10);
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
