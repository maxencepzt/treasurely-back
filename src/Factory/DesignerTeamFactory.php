<?php

namespace App\Factory;

use App\Entity\DesignerTeam;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<DesignerTeam>
 */
final class DesignerTeamFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return DesignerTeam::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->company(),
            'description' => self::faker()->text(500),
            'owner' => UserFactory::new(),
            'image' => PictureFactory::new(),
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (DesignerTeam $team): void {
                $team->addMember($team->getOwner());
                for ($i = 0; $i < rand(3, 7); ++$i) {
                    $user = UserFactory::random();
                    $team->addMember($user->_real());
                }
            })
        ;
    }
}
