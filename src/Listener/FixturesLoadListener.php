<?php

namespace App\Listener;

use App\Repository\UserRepository;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ConsoleEvents::TERMINATE)]
class FixturesLoadListener
{
    private bool $hasBeenExecuted = false;

    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function __invoke(ConsoleTerminateEvent $event): void
    {
        // Ne s'exécute que pour la commande doctrine:fixtures:load et une seule fois
        if ($this->hasBeenExecuted || !str_contains($event->getCommand()?->getName() ?? '', 'doctrine:fixtures:load')) {
            return;
        }

        $this->hasBeenExecuted = true;
        $output = $event->getOutput();

        $output->writeln('<info>Updating user statistics...</info>');

        $entityManager = $this->userRepository->createQueryBuilder('u')->getEntityManager();
        $users = $this->userRepository->findAll();
        $updatedCount = 0;

        foreach ($users as $user) {
            $totalHunt = $this->userRepository->getTotalHunt($user);
            $totalScore = $this->userRepository->getTotalScore($user);
            $totalTime = $this->userRepository->getTotalTime($user);
            $totalRiddles = $this->userRepository->getTotalRiddles($user);

            $user->setTotalHunt($totalHunt);
            $user->setTotalScore($totalScore);
            $user->setTotalTime($totalTime);
            $user->setTotalRiddles($totalRiddles);

            $entityManager->persist($user);
            ++$updatedCount;
        }

        $entityManager->flush();

        $output->writeln(sprintf('<info>✓ Updated statistics for %d users</info>', $updatedCount));
    }
}
