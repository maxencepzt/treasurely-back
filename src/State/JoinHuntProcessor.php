<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\ParticipateHunt;
use App\Entity\User;
use App\Repository\ParticipateHuntRepository;
use App\Repository\RiddleRepository;
use App\Service\ScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Rejoindre une chasse : le joueur choisit la chasse et, s'il le souhaite, une de ses
 * équipes ; le serveur fixe le joueur, la première énigme et la date.
 *
 * @implements ProcessorInterface<ParticipateHunt, ParticipateHunt>
 */
final class JoinHuntProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly ScoreCalculator $scoreCalculator,
        private readonly ParticipateHuntRepository $participations,
        private readonly RiddleRepository $riddles,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ParticipateHunt
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);
        $hunt = $data->getHunt();
        \assert(null !== $hunt); // Assert\NotNull a déjà refusé un corps sans chasse

        if (!$hunt->isOpened()) {
            throw new ConflictHttpException("Cette chasse n'est pas ouverte.");
        }
        if (!$this->scoreCalculator->canUserPlay($user, $hunt)) {
            throw new AccessDeniedHttpException('Vous ne pouvez pas jouer une chasse que vous avez conçue.');
        }
        if (null !== $this->participations->findOneBy(['hunter' => $user, 'hunt' => $hunt])) {
            throw new ConflictHttpException('Vous participez déjà à cette chasse.');
        }
        $team = $data->getPlayerTeam();
        if (null !== $team && !$team->hasMember($user)) {
            throw new AccessDeniedHttpException("Vous n'êtes pas membre de cette équipe.");
        }
        $first = $this->riddles->findByTreasureHunt($hunt)[0]
            ?? throw new ConflictHttpException("Cette chasse n'a pas encore d'énigme.");

        $data->setHunter($user)
            ->setCurrentRiddle($first)
            ->setLastParticipate(new \DateTimeImmutable());
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
