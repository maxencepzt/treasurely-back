<?php

namespace App\DataFixtures;

use App\Factory\PictureFactory;
use App\Factory\TeamFactory;
use App\Factory\TreasureHuntFactory;
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

        TreasureHuntFactory::createone([
            'image' => PictureFactory::createOne(),
            'team' => TeamFactory::createOne(),
            'owner' => TeamFactory::random(),
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
