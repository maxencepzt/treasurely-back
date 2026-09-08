<?php

namespace App\Listener;

use App\Entity\ParticipateRiddle;
use App\Entity\User;
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
    /** @var array<int, User> */
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
                $this->usersToUpdate[$hunter->getId()] = $hunter;
            }
        }

        // Check for updated ParticipateRiddle entities (finishTime changes)
        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof ParticipateRiddle) {
                $hunter = $entity->getHunter();
                // Check if finishTime was updated
                $changeSet = $unitOfWork->getEntityChangeSet($entity);
                if (isset($changeSet['finishTime'])) {
                    $this->usersToUpdate[$hunter->getId()] = $hunter;
                }
            }
        }

        // Check for deleted ParticipateRiddle entities
        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof ParticipateRiddle) {
                $hunter = $entity->getHunter();
                $this->usersToUpdate[$hunter->getId()] = $hunter;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if ([] === $this->usersToUpdate || $this->isUpdating) {
            return;
        }

        $this->isUpdating = true;
        $entityManager = $event->getObjectManager();
        $usersToProcess = $this->usersToUpdate;
        $this->usersToUpdate = [];

        foreach ($usersToProcess as $user) {
            // Un utilisateur supprimé dans ce même flush (avec ses participations) n'a plus de statistiques à tenir.
            if (!$entityManager->contains($user)) {
                continue;
            }

            // Refresh pour avoir les dernières données
            $entityManager->refresh($user);

            // Calculer le nombre total de riddles
            $totalRiddles = $this->userRepository->getTotalRiddles($user);
            $user->setTotalRiddles($totalRiddles);

            $entityManager->persist($user);
        }

        $entityManager->flush();

        $this->isUpdating = false;
    }
}
