<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Hache le mot de passe reçu en clair (`User::$plainPassword`) avant de laisser API Platform
 * persister l'utilisateur, à l'inscription comme à la modification du profil.
 *
 * @implements ProcessorInterface<User, User>
 */
final class UserPasswordProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<User, User> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (null !== $data->getPlainPassword()) {
            $data->setPassword($this->passwordHasher->hashPassword($data, $data->getPlainPassword()));
            $data->eraseCredentials();
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
