<?php

namespace App\Factory;

use App\Entity\MCQRiddle;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<MCQRiddle>
 */
final class MCQRiddleFactory extends PersistentProxyObjectFactory
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
        return MCQRiddle::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'answers' => ['réponse1', 'réponse3'],
            'choices' => ['réponse1', 'réponse2', 'réponse3', 'réponse4'],
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
            // ->afterInstantiate(function(MCQRiddle $mCQRiddle): void {})
        ;
    }
}
