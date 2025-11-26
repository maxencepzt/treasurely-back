<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-user-stats',
    description: 'Update all user statistics (totalHunt, totalScore, totalTime, totalRiddles) based on their participations'
)]
class UpdateUserStatsCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->userRepository->findAll();
        $updatedCount = 0;

        $io->progressStart(count($users));

        foreach ($users as $user) {
            // Calculer toutes les statistiques
            $totalHunt = $this->userRepository->getTotalHunt($user);
            $totalScore = $this->userRepository->getTotalScore($user);
            $totalTime = $this->userRepository->getTotalTime($user);
            $totalRiddles = $this->userRepository->getTotalRiddles($user);

            // Mettre à jour l'utilisateur
            $user->setTotalHunt($totalHunt);
            $user->setTotalScore($totalScore);
            $user->setTotalTime($totalTime);
            $user->setTotalRiddles($totalRiddles);

            $this->entityManager->persist($user);
            ++$updatedCount;
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success(sprintf('Successfully updated all statistics for %d users.', $updatedCount));

        return Command::SUCCESS;
    }
}
