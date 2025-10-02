<?php

namespace App\DataFixtures;

use App\Factory\GPSRiddleFactory;
use App\Factory\MCQRiddleFactory;
use App\Factory\QRRiddleFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\TreasureHuntFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RiddleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $factories = [
            QRRiddleFactory::class,
            TextRiddleFactory::class,
            GPSRiddleFactory::class,
            MCQRiddleFactory::class,
        ];

        $hunts = TreasureHuntFactory::all();

        foreach ($factories as $factory) {
            $factory::createMany(5, fn () => ['hunt' => $hunts[array_rand($hunts)]]);
        }
    }

    public function getDependencies(): array
    {
        return [
            TreasureHuntFixtures::class,
        ];
    }
}
