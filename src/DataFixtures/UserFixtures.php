<?php

namespace App\DataFixtures;

use App\Factory\PictureFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; ++$i) {
            UserFactory::createOne(['profilePicture' => PictureFactory::CreateOne()]);
        }
    }
}
