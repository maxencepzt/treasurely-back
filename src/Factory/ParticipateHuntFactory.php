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
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $user = UserFactory::random();
        $hunt = TreasureHuntFactory::random();

        $lastParticipate = \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('today - 300 days', 'today - 250 days'));

        $service = new ScoreCalculator();

        return [
            'lastParticipate' => $lastParticipate,
            'score' => $service->totalScorePerHunt($hunt, $user, $lastParticipate)[0],
            'time' => $service->totalScorePerHunt($hunt, $user, $lastParticipate)[1],
            'finished' => $service->totalScorePerHunt($hunt, $user, $lastParticipate)[2],
            'hunter' => $user,
            'hunt' => $hunt,
        ];
    }
}
