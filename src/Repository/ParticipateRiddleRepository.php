<?php

namespace App\Repository;

use App\Entity\ParticipateRiddle;
use App\Entity\Riddle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParticipateRiddle>
 */
class ParticipateRiddleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipateRiddle::class);
    }

    /**
     * Nombre de participations par énigme, en une requête, pour décider ce qui peut
     * encore être supprimé ou retypé sans perte.
     *
     * @param Riddle[] $riddles
     *
     * @return array<int, int> identifiant d'énigme => nombre de participations
     */
    public function countByRiddle(array $riddles): array
    {
        if ([] === $riddles) {
            return [];
        }

        $rows = $this->createQueryBuilder('pr')
            ->select('IDENTITY(pr.riddle) AS riddleId, COUNT(pr.id) AS total')
            ->andWhere('pr.riddle IN (:riddles)')
            ->setParameter('riddles', $riddles)
            ->groupBy('pr.riddle')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['riddleId']] = (int) $row['total'];
        }

        return $counts;
    }
}
