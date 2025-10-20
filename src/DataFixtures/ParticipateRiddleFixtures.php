<?php

namespace App\DataFixtures;

use App\Factory\ParticipateRiddleFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ParticipateRiddleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        ParticipateRiddleFactory::createMany(10, [
            'hunter' => UserFactory::random(),
        ]);
    }

    /**
     * @return class-string<FixtureInterface>[]
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            RiddleFixtures::class,
        ];
    }
}
