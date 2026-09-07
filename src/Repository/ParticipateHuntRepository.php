<?php

namespace App\Repository;

use App\Entity\ParticipateHunt;
use App\Entity\TreasureHunt;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParticipateHunt>
 */
class ParticipateHuntRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipateHunt::class);
    }

    /**
     * Fréquentation d'une chasse pour son concepteur : inscrits, finisseurs, et la moyenne
     * de ces derniers. Deux requêtes d'agrégats, aucune ligne chargée.
     *
     * @return array{participations: int, finished: int, averageScore: ?int, averageTime: ?int}
     */
    public function statsForHunt(TreasureHunt $hunt): array
    {
        $counts = $this->createQueryBuilder('ph')
            ->select('COUNT(ph.id) AS participations')
            ->addSelect('SUM(CASE WHEN ph.finished = true THEN 1 ELSE 0 END) AS finished')
            ->andWhere('ph.hunt = :hunt')
            ->setParameter('hunt', $hunt)
            ->getQuery()
            ->getSingleResult();
        $averages = $this->createQueryBuilder('ph')
            ->select('AVG(ph.score) AS score', 'AVG(ph.time) AS time')
            ->andWhere('ph.hunt = :hunt')
            ->andWhere('ph.finished = true')
            ->setParameter('hunt', $hunt)
            ->getQuery()
            ->getSingleResult();

        return [
            'participations' => (int) $counts['participations'],
            'finished' => (int) $counts['finished'],
            'averageScore' => null === $averages['score'] ? null : (int) round((float) $averages['score']),
            'averageTime' => null === $averages['time'] ? null : (int) round((float) $averages['time']),
        ];
    }

    /**
     * Inscrits, toutes chasses confondues, des équipes de conception que possède l'utilisateur :
     * même périmètre que les autres chiffres du tableau de bord.
     */
    public function countByTeamOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('ph')
            ->select('COUNT(ph.id)')
            ->innerJoin('ph.hunt', 'th')
            ->innerJoin('th.designerTeam', 't')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Nombre d'inscrits par chasse, en une requête, pour la liste du concepteur.
     *
     * @param TreasureHunt[] $hunts
     *
     * @return array<int, int> identifiant de chasse => nombre de participations
     */
    public function countByHunt(array $hunts): array
    {
        if ([] === $hunts) {
            return [];
        }

        $rows = $this->createQueryBuilder('ph')
            ->select('IDENTITY(ph.hunt) AS huntId, COUNT(ph.id) AS total')
            ->andWhere('ph.hunt IN (:hunts)')
            ->setParameter('hunts', $hunts)
            ->groupBy('ph.hunt')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['huntId']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Classement des finisseurs d'une chasse : meilleur score, puis temps le plus court,
     * puis première arrivée. Joueur et équipe sont chargés avec, le classement les affiche.
     *
     * @return ParticipateHunt[]
     */
    public function findRanking(TreasureHunt $hunt): array
    {
        return $this->createQueryBuilder('ph')
            ->addSelect('hunter', 'team')
            ->join('ph.hunter', 'hunter')
            ->leftJoin('ph.playerTeam', 'team')
            ->andWhere('ph.hunt = :hunt')
            ->andWhere('ph.finished = true')
            ->setParameter('hunt', $hunt)
            ->orderBy('ph.score', 'DESC')
            ->addOrderBy('ph.time', 'ASC')
            ->addOrderBy('ph.lastParticipate', 'ASC')
            ->addOrderBy('ph.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
