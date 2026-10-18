<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Hache le mot de passe reçu en clair (`User::$plainPassword`) avant de laisser API Platform
 * persister l'utilisateur, à l'inscription comme à la modification du profil.
 *
 * Un changement de mot de passe ou de pseudo révoque les refresh tokens du compte : ils sont
 * rattachés au pseudo, et ni un jeton volé ni un pseudo libéré puis repris par un autre joueur
 * ne doivent rouvrir de session.
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
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $previous = $context['previous_data'] ?? null;
        $revoke = $previous instanceof User
            && (null !== $data->getPlainPassword() || $previous->getNickname() !== $data->getNickname());

        if (null !== $data->getPlainPassword()) {
            $data->setPassword($this->passwordHasher->hashPassword($data, $data->getPlainPassword()));
            $data->eraseCredentials();
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if ($revoke) {
            $this->entityManager->createQuery('DELETE FROM '.RefreshToken::class.' t WHERE t.username = :username')
                ->setParameter('username', $previous->getNickname())
                ->execute();
        }

        return $result;
    }
}
