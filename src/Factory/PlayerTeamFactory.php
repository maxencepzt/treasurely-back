<?php

namespace App\Factory;

use App\Entity\PlayerTeam;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<PlayerTeam>
 */
final class PlayerTeamFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return PlayerTeam::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->company(),
            'description' => self::faker()->text(500),
            'owner' => UserFactory::new(),
            'image' => PictureFactory::new(),
            'code' => self::faker()->numerify('treasurely_#############'),
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (PlayerTeam $team): void {
                $team->addMember($team->getOwner());
                for ($i = 0; $i < rand(3, 7); ++$i) {
                    $user = UserFactory::random();
                    $team->addMember($user->_real());
                }
            })
        ;
    }
}
