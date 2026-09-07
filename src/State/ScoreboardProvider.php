<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\ParticipateHunt;
use App\Repository\ParticipateHuntRepository;
use App\Repository\TreasureHuntRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Dix finisseurs à qui se comparer. Qui n'a jamais terminé la chasse voit les dix premiers ;
 * qui l'a terminée voit cinq joueurs au-dessus de lui et quatre en dessous, la fenêtre glissant
 * aux deux bouts pour toujours en montrer dix.
 *
 * @implements ProviderInterface<ParticipateHunt>
 */
final class ScoreboardProvider implements ProviderInterface
{
    public const int SIZE = 10;
    public const int ABOVE = 5;

    public function __construct(
        private readonly Security $security,
        private readonly TreasureHuntRepository $hunts,
        private readonly ParticipateHuntRepository $participations,
    ) {
    }

    /**
     * @return ParticipateHunt[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $hunt = $this->hunts->find($uriVariables['id'] ?? 0)
            ?? throw new NotFoundHttpException('Chasse introuvable.');

        // ponytail: tout le classement est chargé ; passer à OFFSET/LIMIT si une chasse dépasse quelques milliers de finisseurs.
        $ranking = $this->participations->findRanking($hunt);
        $viewer = $this->security->getUser();
        $position = null;
        foreach ($ranking as $index => $participation) {
            $participation->setRank($index + 1);
            if ($participation->getHunter() === $viewer) {
                $position = $index;
            }
        }

        $start = null === $position ? 0 : max(0, min($position - self::ABOVE, \count($ranking) - self::SIZE));

        return \array_slice($ranking, $start, self::SIZE);
    }
}
