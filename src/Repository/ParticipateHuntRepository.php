<?php

namespace App\Repository;

use App\Entity\ParticipateHunt;
use App\Entity\TreasureHunt;
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
