<?php

namespace App\Factory;

use App\Entity\TreasureHunt;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<TreasureHunt>
 */
final class TreasureHuntFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return TreasureHunt::class;
    }

    protected function defaults(): array|callable
    {
        $team = TeamFactory::random();
        $owner = self::faker()->randomElement($team->getMembers());

        return [
            'title' => self::faker()->text(20),
            'description' => self::faker()->text(3000),
            'public' => self::faker()->boolean(),
            'difficulty' => self::faker()->numberBetween(1, 3),
            'riddleCount' => self::faker()->numberBetween(3, 10),
            'team' => $team,
            'image' => PictureFactory::new(),
            'owner' => $owner,
            'location' => self::faker()->city(),
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (TreasureHunt $treasureHunt): void {
                $treasureHunt->addHuntType(HuntTypeFactory::random()->_real());
            })
        ;
    }
}
