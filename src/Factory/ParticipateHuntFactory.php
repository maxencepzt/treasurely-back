<?php

namespace App\Factory;

use App\Entity\ParticipateHunt;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ParticipateHunt>
 */
final class ParticipateHuntFactory extends PersistentProxyObjectFactory
{
    public static Generator $faker;

    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return ParticipateHunt::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        $user = UserFactory::random();
        $hunt = TreasureHuntFactory::random();

        $lastParticipate = \DateTimeImmutable::createFromMutable(
            self::faker()->dateTimeBetween('today +20 days', 'today +30 days')
        );

        $finished = self::faker()->boolean(70);

        if ($finished) {
            $score = self::faker()->numberBetween(1000, 5000);
            $time = self::faker()->numberBetween(600, 7200); // Entre 10 min et 2h en secondes
        } else {
            $hasStarted = self::faker()->boolean(50);
            $score = $hasStarted ? self::faker()->numberBetween(100, 2000) : 0;
            $time = $hasStarted ? self::faker()->numberBetween(300, 3600) : 0;
        }

        return [
            'lastParticipate' => $lastParticipate,
            'score' => $score,
            'time' => $time,
            'finished' => $finished,
            'hunter' => $user,
            'hunt' => $hunt,
            'rate' => self::faker()->optional(0.7)->numberBetween(0, 5),
        ];
    }
}
