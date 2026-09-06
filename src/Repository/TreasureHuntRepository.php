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
     * @return TreasureHunt[]
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('th')
            ->addSelect('t', 'r')
            ->innerJoin('th.designerTeam', 't')
            ->leftJoin('th.riddles', 'r')
            ->andWhere('th.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('th.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, int> statut => nombre de chasses, mêmes chasses que findByOwner()
     */
    public function countByStatusForOwner(User $owner): array
    {
        $rows = $this->createQueryBuilder('th')
            ->select('th.status AS status, COUNT(th.id) AS total')
            ->andWhere('th.owner = :owner')
            ->setParameter('owner', $owner)
            ->groupBy('th.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        return $counts;
    }
}
