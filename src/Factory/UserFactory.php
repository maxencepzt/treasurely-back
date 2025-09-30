<?php

namespace App\Factory;

use App\Entity\User;
use App\Enum\Gender;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    private UserPasswordHasherInterface $passwordHasher;

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public static function class(): string
    {
        return User::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'activated' => true,
            'birthDate' => self::faker()->dateTime(),
            'email' => self::faker()->text(50),
            'firstname' => self::faker()->name(),
            'gender' => self::faker()->randomElement(Gender::cases()),
            'lastLogin' => self::faker()->dateTime(),
            'lastname' => self::faker()->lastName(),
            'nickname' => self::faker()->name(),
            'password' => 'test',
            'phone' => self::faker()->numerify('## ## ## ## ##'),
            'profilePicture' => PictureFactory::new(),
            'public' => true,
            'roles' => [],
            'totalHunt' => self::faker()->randomNumber(),
            'totalTime' => self::faker()->randomNumber(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (User $user): void {
                $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
            })
        ;
    }
}
