<?php

namespace App\Listener;

use App\Entity\ParticipateRiddle;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class ParticipateRiddleListener implements EventSubscriber
{
    private array $usersToUpdate = [];
    private bool $isUpdating = false;

    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
            Events::postFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        if ($this->isUpdating) {
            return;
        }

        $entityManager = $event->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        // Check for inserted ParticipateRiddle entities
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof ParticipateRiddle) {
                $hunter = $entity->getHunter();
                if (null !== $hunter) {
                    $this->usersToUpdate[$hunter->getId()] = $hunter;
                }
            }
        }

        // Check for deleted ParticipateRiddle entities
        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof ParticipateRiddle) {
                $hunter = $entity->getHunter();
                if (null !== $hunter) {
                    $this->usersToUpdate[$hunter->getId()] = $hunter;
                }
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if (empty($this->usersToUpdate) || $this->isUpdating) {
            return;
        }

        $this->isUpdating = true;
        $entityManager = $event->getObjectManager();
        $usersToProcess = $this->usersToUpdate;
        $this->usersToUpdate = [];

        foreach ($usersToProcess as $user) {
            // Refresh pour avoir les dernières données
            $entityManager->refresh($user);

            // Calculer le nombre total de riddles
            $totalRiddles = $this->userRepository->getTotalRiddles($user);
            $user->setTotalRiddles($totalRiddles);

            $entityManager->persist($user);
        }

        if (!empty($usersToProcess)) {
            $entityManager->flush();
        }

        $this->isUpdating = false;
    }
}
