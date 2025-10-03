<?php

namespace App\Factory;

use App\Entity\ParticipateHunt;
use App\Entity\Riddle;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ParticipateHunt>
 */
final class ParticipateHuntFactory extends PersistentProxyObjectFactory
{
    public static Generator $faker;

    private int $base;
    private float $alpha;

    public function __construct(int $base = 1000, float $alpha = 0.001)
    {
        $this->base = $base;
        $this->alpha = $alpha;
    }

    public static function class(): string
    {
        return ParticipateHunt::class;
    }

    public static function initializeFaker(): void
    {
        self::$faker = self::faker();
    }

    /**
     * @param Riddle[] $riddles
     */
    public function calculateScore(array $riddles): int
    {
        $scoreTotal = 0;

        foreach ($riddles as $riddle) {
            // $enigme = ['temps' => int, 'difficulte' => int]
            $time = $riddle['time'];
            $difficulty = $riddle['difficulty'];

            $score = $this->base * exp(-$this->alpha * $time);
            $scoreTotal += $difficulty * $score;
        }

        return (int) floor($scoreTotal);
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'lastParticipate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime('today + 10 days')),
            'score' => self::faker()->randomNumber(),
            'time' => self::faker()->numberBetween(1800, 7200),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this->afterInstantiate(function (ParticipateHunt $participateHunt): void {
            $riddles = $participateHunt->getHunt()->getRiddles();
            $participateHunt->setScore($this->calculateScore((array) $riddles));
        });
    }
}
