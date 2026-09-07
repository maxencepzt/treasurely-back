<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\ParticipateRiddle;
use App\Entity\Riddle;
use App\Entity\User;
use App\Repository\ParticipateRiddleRepository;
use App\Service\ScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Lire une énigme démarre son chronomètre. Le départ est daté par le serveur, à l'instant
 * où l'énoncé est remis au joueur : le client ne peut ni l'avancer ni le retarder.
 *
 * @implements ProviderInterface<Riddle>
 */
final class RiddleProvider implements ProviderInterface
{
    /**
     * @param ProviderInterface<Riddle> $itemProvider
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private readonly ProviderInterface $itemProvider,
        private readonly Security $security,
        private readonly ScoreCalculator $scoreCalculator,
        private readonly ParticipateRiddleRepository $participations,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $riddle = $this->itemProvider->provide($operation, $uriVariables, $context);
        $user = $this->security->getUser();

        if ($riddle instanceof Riddle
            && $user instanceof User
            && $riddle->getHunt()?->isOpened()
            && $this->scoreCalculator->canUserParticipate($user, $riddle)
            && null === $this->participations->findOneBy(['hunter' => $user, 'riddle' => $riddle])
        ) {
            $participation = (new ParticipateRiddle())
                ->setHunter($user)
                ->setRiddle($riddle)
                ->setLastParticipate(new \DateTime());
            $this->entityManager->persist($participation);
            $this->entityManager->flush();
        }

        return $riddle;
    }
}
