<?php

namespace App\DataFixtures;

use App\Factory\PictureFactory;
use App\Factory\TeamFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TreasureHuntFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        TreasureHuntFactory::createMany(10, [
            'image' => PictureFactory::createOne(),
        ]);

        $team = TeamFactory::createOne();
        $members = UserFactory::createMany(5, ['team' => $team]);
        $owner = $members[array_rand($members)];

        TreasureHuntFactory::createOne([
            'image' => PictureFactory::createOne(),
            'team' => $team,
            'owner' => $owner,
        ]);
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
