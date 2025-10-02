<?php

namespace App\Factory;

use App\Entity\QRRiddle;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<QRRiddle>
 */
final class QRRiddleFactory extends PersistentProxyObjectFactory
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
        return QRRiddle::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'code' => 'treasurely_'.self::faker()->randomNumber(12),
            'description' => self::faker()->text(1000),
            'difficulty' => self::faker()->numberBetween(1, 3),
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
            // ->afterInstantiate(function(QRRiddle $qRRiddle): void {})
        ;
    }
}
