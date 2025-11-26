<?php

namespace App\Repository;

use App\Entity\DesignerTeam;
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
     * Find treasure hunts by designer team.
     *
     * @return TreasureHunt[]
     */
    public function findByDesignerTeam(DesignerTeam $designerTeam): array
    {
        return $this->createQueryBuilder('th')
            ->andWhere('th.designerTeam = :designerTeam')
            ->andWhere('th.status != :draft')
            ->setParameter('designerTeam', $designerTeam)
            ->setParameter('draft', TreasureHunt::STATE_DRAFT)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count treasure hunts in teams owned by the user.
     */
    public function countByTeamOwner(User $owner): int
    {
        return $this->createQueryBuilder('th')
            ->select('COUNT(th.id)')
            ->innerJoin('th.designerTeam', 't')
            ->andWhere('t.owner = :owner')
            ->andWhere('th.status != :draft')
            ->setParameter('draft', TreasureHunt::STATE_DRAFT)
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<string, int> an associative array where keys are statuses and values are counts
     */
    public function countStatusByOwner(User $owner): array
    {
        $results = $this->createQueryBuilder('th')
            ->select('th.status, COUNT(th.id) as count')
            ->innerJoin('th.designerTeam', 't')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->groupBy('th.status')
            ->getQuery()
            ->getResult();

        $statusCounts = [];
        foreach ($results as $result) {
            $statusCounts[$result['status']] = (int) $result['count'];
        }

        return $statusCounts;
    }
}
