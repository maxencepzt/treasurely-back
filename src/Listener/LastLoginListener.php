<?php

declare(strict_types=1);

namespace App\Listener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Date la dernière activité d'un joueur à chaque JWT émis : à la connexion et à chaque
 * rafraîchissement, si bien qu'une session restée ouverte compte aussi. La purge des comptes
 * inactifs (app:users:purge-inactive) s'appuie sur cette date.
 */
#[AsEventListener(event: Events::AUTHENTICATION_SUCCESS)]
final readonly class LastLoginListener
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $user->setLastLogin(new \DateTime());
        $this->entityManager->flush();
    }
}
