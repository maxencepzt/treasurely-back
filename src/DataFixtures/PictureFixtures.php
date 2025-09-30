<?php

namespace App\DataFixtures;

use App\Factory\PictureFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PictureFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        PictureFactory::createMany(3);
    }
}
