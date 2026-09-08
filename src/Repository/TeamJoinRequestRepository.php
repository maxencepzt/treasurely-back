<?php

namespace App\Repository;

use App\Entity\PlayerTeam;
use App\Entity\TeamJoinRequest;
use App\Entity\User;
use App\Enum\JoinRequestStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamJoinRequest>
 */
class TeamJoinRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamJoinRequest::class);
    }

    /**
     * @return TeamJoinRequest[]
     */
    public function findPending(PlayerTeam $team): array
    {
        return $this->findBy(['team' => $team, 'status' => JoinRequestStatus::PENDING], ['createdAt' => 'ASC']);
    }

    /**
     * @return TeamJoinRequest[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }
}
