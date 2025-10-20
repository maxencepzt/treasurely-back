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
        return [
            'startTime' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime('today')),
            'finishTime' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime('today + 10 days')),
            'score' => self::faker()->randomNumber(),
            'lastParticipate' => self::faker()->dateTime(),
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
