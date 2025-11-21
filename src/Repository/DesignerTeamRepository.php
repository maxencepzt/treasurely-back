<?php

namespace App\Repository;

use App\Entity\DesignerTeam;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DesignerTeam>
 */
class DesignerTeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DesignerTeam::class);
    }

    /**
     * @return DesignerTeam[] Returns an array of Team objects owned by the user
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return DesignerTeam[] Returns an array of Team objects where user is a member
     */
    public function findByMemberOnly(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.members', 'm')
            ->andWhere('m = :user')
            ->andWhere('t.owner != :user')
            ->setParameter('user', $user)
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count teams owned by the user.
     */
    public function countByOwner(User $owner): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count total members in all teams owned by the user.
     */
    public function countMembersByOwner(User $owner): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT m.id)')
            ->innerJoin('t.members', 'm')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
