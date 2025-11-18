<?php

namespace App\Factory;

use App\Entity\ParticipateHunt;
use App\Service\ScoreCalculator;
use App\Entity\Riddle;
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

        $lastParticipate = \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('today - 300 days', 'today - 250 days'));

        $service = new ScoreCalculator();
        [$score, $time, $finished] = $service->totalScorePerHunt($hunt, $user, $lastParticipate);

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
