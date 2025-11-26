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
    private static ?\Transliterator $transliterator;

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
        self::$transliterator = \Transliterator::create('Any-Latin ; ASCII-Latin ; Any-Lower ; Latin-ASCII');
    }

    public static function class(): string
    {
        return User::class;
    }

    public static function normalizeName(string $name): string
    {
        $name = self::$transliterator->transliterate($name);
        $name = strtolower($name);
        $name = preg_replace('/[^a-z]/', '-', $name);

        return $name;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $firstname = self::faker()->unique()->firstName();
        $lastname = self::faker()->unique()->lastName();
        $nickname = substr(self::normalizeName($firstname), 0, 1).self::normalizeName($lastname);
        $email = $nickname.'@treasurely.com';

        return [
            'activated' => true,
            'birthDate' => self::faker()->dateTime('now - 10 years'),
            'email' => $email,
            'firstname' => $firstname,
            'gender' => self::faker()->randomElement(Gender::cases()),
            'lastLogin' => self::faker()->dateTime(),
            'lastname' => $lastname,
            'nickname' => $nickname,
            'password' => 'test',
            'phone' => self::faker()->e164PhoneNumber(),
            'profilePicture' => PictureFactory::new(),
            'public' => true,
            'roles' => [],
            'description' => self::faker()->text(150),
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
