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

        foreach ($hunts as $hunt) {
            foreach ($factories as $order => $factory) {
                $factory::createOne(fn () => [
                    'hunt' => $hunt,
                    'orderNumber' => $order + 1,
                ]);
            }
            $hunt->setRiddleCount(count($hunt->getRiddles()));
        }
    }

    public function getDependencies(): array
    {
        return [
            TreasureHuntFixtures::class,
        ];
    }
}
