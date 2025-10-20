<?php

namespace App\Factory;

use App\Entity\ParticipateRiddle;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ParticipateRiddle>
 */
final class ParticipateRiddleFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return ParticipateRiddle::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $startTime = \DateTimeImmutable::createFromMutable(self::faker()->dateTime('today'));
        $lastParticipate = \DateTimeImmutable::createFromMutable(self::faker()->dateTime('today + 10 days'));
        if ($startTime > $lastParticipate) {
            $lastParticipate = \DateTimeImmutable::createFromMutable(self::faker()->dateTime('today + 10 days'));
        }

        $riddleFactory = [GPSRiddleFactory::random(), MCQRiddleFactory::random(), QRRiddleFactory::random(), TextRiddleFactory::random()];
        $riddle = $riddleFactory[array_rand($riddleFactory)];
        $difficulty = $riddle->getDifficulty();

        $score = 0;
        $finishTime = null;
        $finish = (bool) rand(0, 1);

        if ($finish) {
            $finishTime = $lastParticipate;
            $score = $difficulty * (1000 * exp(-0.001 * (int) abs($finishTime->getTimestamp() - $startTime->getTimestamp()))); // Valeurs d'exemples pour le calcul du score
        }

        return [
            'startTime' => $startTime,
            'finishTime' => $finishTime,
            'score' => $score,
            'lastParticipate' => $lastParticipate,
            'hunter' => UserFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(ParticipateRiddle $participateRiddle): void {})
        ;
    }
}
