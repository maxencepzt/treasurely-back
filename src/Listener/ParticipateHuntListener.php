<?php

namespace App\Listener;

use App\Entity\ParticipateHunt;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class ParticipateHuntListener implements EventSubscriber
{
    /**
     * @var array<int, User>
     */
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

        // Check for inserted ParticipateHunt entities
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof ParticipateHunt) {
                $hunter = $entity->getHunter();
                if (null !== $hunter) {
                    $this->usersToUpdate[$hunter->getId()] = $hunter;
                }
            }
        }

        // Check for updated ParticipateHunt entities (score/time changes)
        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof ParticipateHunt) {
                $hunter = $entity->getHunter();
                if (null !== $hunter) {
                    $this->usersToUpdate[$hunter->getId()] = $hunter;
                }
            }
        }

        // Check for deleted ParticipateHunt entities
        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof ParticipateHunt) {
                $hunter = $entity->getHunter();
                if (null !== $hunter) {
                    $this->usersToUpdate[$hunter->getId()] = $hunter;
                }
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if ($this->isUpdating || 0 === count($this->usersToUpdate)) {
            return;
        }

        $this->isUpdating = true;
        $entityManager = $event->getObjectManager();
        $usersToProcess = $this->usersToUpdate;
        $this->usersToUpdate = [];

        foreach ($usersToProcess as $user) {
            // Refresh pour avoir les dernières données
            $entityManager->refresh($user);

            // Calculer toutes les statistiques liées à ParticipateHunt
            $totalHunt = $this->userRepository->getTotalHunt($user);
            $totalScore = $this->userRepository->getTotalScore($user);
            $totalTime = $this->userRepository->getTotalTime($user);

            $user->setTotalHunt($totalHunt);
            $user->setTotalScore($totalScore);
            $user->setTotalTime($totalTime);

            $entityManager->persist($user);
        }

        // On flush, car on sait que des utilisateurs ont été modifiés
        $entityManager->flush();

        $this->isUpdating = false;
    }
}
