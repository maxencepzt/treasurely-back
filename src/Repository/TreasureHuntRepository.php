<?php

namespace App\Repository;

use App\Entity\TreasureHunt;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TreasureHunt>
 */
class TreasureHuntRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TreasureHunt::class);
    }

    /**
     * Count treasure hunts in teams owned by the user.
     */
    public function countByTeamOwner(User $owner): int
    {
        return $this->createQueryBuilder('th')
            ->select('COUNT(th.id)')
            ->innerJoin('th.team', 't')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
