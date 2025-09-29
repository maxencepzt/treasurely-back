<?php

namespace App\DataFixtures;

use App\Story\HuntTypeStory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class HuntTypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        HuntTypeStory::load();
    }
}
