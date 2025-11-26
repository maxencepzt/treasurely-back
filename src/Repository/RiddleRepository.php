<?php

namespace App\Repository;

use App\Entity\Riddle;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Riddle>
 */
class RiddleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Riddle::class);
    }

    /**
     * Count riddles in treasure hunts of teams owned by the user.
     */
    public function countByTeamOwner(User $owner): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->innerJoin('r.hunt', 'th')
            ->innerJoin('th.designerTeam', 't')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
