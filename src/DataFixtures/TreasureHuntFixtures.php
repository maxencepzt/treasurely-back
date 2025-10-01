<?php

namespace App\DataFixtures;

use App\Factory\PictureFactory;
use App\Factory\TeamFactory;
use App\Factory\TreasureHuntFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TreasureHuntFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        TreasureHuntFactory::createMany(10, function () {
            $team = TeamFactory::random();
            $members = $team->getMembers();
            $owner = $members[array_rand($members->toArray())];

            return [
                'image' => PictureFactory::createOne(),
                'team' => $team,
                'owner' => $owner,
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
            TeamFixtures::class,
            PictureFixtures::class,
        ];
    }
}
