<?php

namespace App\DataFixtures;

use App\Factory\PictureFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createOne([
            'profilePicture' => PictureFactory::createOne(),
            'roles' => ['ROLE_ADMIN'],
            'password' => 'admin',
        ]);
        UserFactory::createMany(40, fn () => [
            'profilePicture' => PictureFactory::createOne(),
        ]);
    }

    public function getDependencies(): array
    {
        return [
            PictureFixtures::class,
        ];
    }
}
