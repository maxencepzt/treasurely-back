<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * La durée de conservation annoncée aux joueurs (« Vos données », côté front) : un compte sans
 * connexion depuis trois ans est supprimé, avec ses participations, ses scores et ses refresh
 * tokens. Les administrateurs et les propriétaires d'équipes ou de chasses, que l'API refuse
 * aussi de supprimer d'un bloc, sont listés pour être traités à la main.
 */
#[AsCommand(
    name: 'app:users:purge-inactive',
    description: 'Delete the accounts without any login for three years'
)]
final class PurgeInactiveUsersCommand extends Command
{
    public const string RETENTION = '3 years';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'List the accounts without deleting them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $deleted = [];
        $kept = [];
        foreach ($this->userRepository->findLastLoginBefore(new \DateTime('-'.self::RETENTION)) as $user) {
            if (in_array('ROLE_ADMIN', $user->getRoles(), true)
                || !$user->getOwnedTeams()->isEmpty()
                || !$user->getTreasureHunts()->isEmpty()) {
                $kept[] = $user->getNickname();
                continue;
            }

            $deleted[] = $user->getNickname();
            if (!$dryRun) {
                $this->remove($user);
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->success(sprintf('%d inactive account(s) %s: %s', count($deleted), $dryRun ? 'to delete' : 'deleted', implode(', ', $deleted) ?: 'none'));
        if ([] !== $kept) {
            $io->warning(sprintf('Kept, to handle by hand (administrator or owner of a team or a hunt): %s', implode(', ', $kept)));
        }

        return Command::SUCCESS;
    }

    private function remove(User $user): void
    {
        $this->entityManager->createQuery('DELETE FROM '.RefreshToken::class.' t WHERE t.username = :username')
            ->setParameter('username', $user->getNickname())
            ->execute();
        $this->entityManager->remove($user);
    }
}
