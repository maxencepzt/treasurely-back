<?php

namespace App\DataFixtures;

use App\Factory\DesignerTeamFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DesignerTeamFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        DesignerTeamFactory::createMany(10);
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
