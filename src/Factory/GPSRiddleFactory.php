<?php

namespace App\Factory;

use App\Entity\GPSRiddle;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<GPSRiddle>
 */
final class GPSRiddleFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return GPSRiddle::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'description' => self::faker()->text(1000),
            'difficulty' => self::faker()->numberBetween(1, 3),
            'latitude' => self::faker()->latitude(),
            'longitude' => self::faker()->longitude(),
            'orderNumber' => self::faker()->randomNumber(),
            'title' => self::faker()->text(20),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(GPSRiddle $gPSRiddle): void {})
        ;
    }
}
