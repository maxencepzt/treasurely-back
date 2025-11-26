<?php

namespace App\Factory;

use App\Entity\TreasureHunt;
use Random\RandomException;
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

    /**
     * @throws RandomException
     */
    protected function defaults(): array|callable
    {
        $team = DesignerTeamFactory::random();
        $owner = self::faker()->randomElement($team->getMembers());

        return [
            'title' => self::faker()->text(20),
            'description' => self::faker()->text(3000),
            'difficulty' => self::faker()->numberBetween(1, 3),
            'riddleCount' => self::faker()->numberBetween(3, 10),
            'designerTeam' => $team,
            'image' => PictureFactory::new(),
            'owner' => $owner,
            'location' => self::faker()->city(),
            'estimatedTime' => random_int(5, 120),
            'status' => self::faker()->randomElement([TreasureHunt::STATE_DRAFT, TreasureHunt::STATE_OPENED, TreasureHunt::STATE_CLOSED]),
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
