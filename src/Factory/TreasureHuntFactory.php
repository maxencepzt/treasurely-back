<?php

namespace App\Factory;

use App\Entity\TreasureHunt;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<TreasureHunt>
 */
final class TreasureHuntFactory extends PersistentProxyObjectFactory
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
        return TreasureHunt::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $minRiddle = 3;

        $team = TeamFactory::random();
        $owner = self::faker()->randomElement($team->getMembers());

        return [
            'title' => self::faker()->text(20),
            'difficulty' => self::faker()->numberBetween(1, 3),
            'riddleCount' => $minRiddle + self::faker()->randomNumber(),
            'public' => self::faker()->boolean(),
            'owner' => $owner,
            'team' => $team,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(TreasureHunt $treasureHunt): void {})
        ;
    }
}
