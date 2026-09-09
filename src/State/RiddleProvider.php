<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\ParticipateRiddle;
use App\Entity\Riddle;
use App\Entity\User;
use App\Repository\ParticipateHuntRepository;
use App\Repository\ParticipateRiddleRepository;
use App\Service\ScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Un joueur ne lit une énigme qu'après avoir rejoint la chasse, et jamais au-delà de celle
 * en cours : une chasse est une séquence dont la résolution conditionne la progression.
 * Lire l'énigme en cours démarre son chronomètre, daté par le serveur à l'instant où
 * l'énoncé est remis : le client ne peut ni l'avancer ni le retarder.
 *
 * Ceux qui ont conçu la chasse, et les administrateurs, lisent tout sans chronomètre.
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
        private readonly ParticipateHuntRepository $huntParticipations,
        private readonly ParticipateRiddleRepository $riddleParticipations,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $riddle = $this->itemProvider->provide($operation, $uriVariables, $context);
        $user = $this->security->getUser();
        $hunt = $riddle instanceof Riddle ? $riddle->getHunt() : null;

        if (null === $hunt || !$user instanceof User) {
            return $riddle;
        }

        // Un administrateur ou un concepteur lit tout sans chrono ; un administrateur qui a rejoint
        // la chasse y joue comme les autres, avec le chrono et la participation que la réponse exige.
        $progress = $this->huntParticipations->findOneBy(['hunter' => $user, 'hunt' => $hunt]);
        if (null === $progress && ($this->security->isGranted('ROLE_ADMIN') || !$this->scoreCalculator->canUserPlay($user, $hunt))) {
            return $riddle;
        }
        if (null === $progress) {
            throw new AccessDeniedHttpException('Rejoignez la chasse pour lire ses énigmes.');
        }
        $current = $progress->getCurrentRiddle();
        if ($riddle->getOrderNumber() > $current->getOrderNumber()) {
            throw new AccessDeniedHttpException("Cette énigme n'est pas encore accessible.");
        }

        if ($current === $riddle
            && !$progress->isFinished()
            && $hunt->isOpened()
            && null === $this->riddleParticipations->findOneBy(['hunter' => $user, 'riddle' => $riddle])
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
