<?php

namespace App\Repository;

use App\Entity\DesignerTeam;
use App\Entity\ParticipateHunt;
use App\Entity\ParticipateRiddle;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Search users by a query string matching nickname, firstname or lastname.
     *
     * @param User[]|null $usersToFilter
     *
     * @return User[]
     */
    public function searchUsersByQuery(string $query, ?array $usersToFilter = null, ?string $searchType = 'exclude'): array
    {
        $query = $this->createQueryBuilder('u')
            ->where('UPPER(u.nickname) LIKE UPPER(:query) OR UPPER(u.firstname) LIKE UPPER(:query) OR UPPER(u.lastname) LIKE UPPER(:query)')
            ->andWhere('u.activated = true')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults(10);

        if ($usersToFilter) {
            if ('exclude' === $searchType) {
                $query->andWhere('u NOT IN (:excludedUsers)');
            } else {
                $query->andWhere('u IN (:excludedUsers)');
            }

            $query->setParameter('excludedUsers', $usersToFilter);
        }

        return $query
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findByDesignerTeam(DesignerTeam $designerTeam): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.teams', 't')
            ->andWhere('t = :team')
            ->andWhere('u != t.owner')
            ->setParameter('team', $designerTeam)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate the total score of a user by summing all scores from their ParticipateHunt entities.
     */
    public function getTotalScore(User $user): int
    {
        $result = $this->createQueryBuilder('u')
            ->select('COALESCE(SUM(ph.score), 0) as totalScore')
            ->leftJoin('u.participateHunts', 'ph')
            ->where('u = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * Calculate the total number of hunts a user has participated in.
     */
    public function getTotalHunt(User $user): int
    {
        $result = $this->createQueryBuilder('u')
            ->select('COUNT(DISTINCT ph.hunt) as totalHunt')
            ->leftJoin('u.participateHunts', 'ph')
            ->where('u = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * Calculate the total number of riddles a user has solved.
     * This includes:
     * - Riddles solved individually (ParticipateRiddle with finishTime)
     * - All riddles from finished hunts (ParticipateHunt with finished = true).
     */
    public function getTotalRiddles(User $user): int
    {
        $entityManager = $this->getEntityManager();

        // Count riddles solved individually
        $riddlesSolvedIndividually = $entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT pr.riddle)')
            ->from(ParticipateRiddle::class, 'pr')
            ->where('pr.hunter = :user')
            ->andWhere('pr.finishTime IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        // Count riddles from finished hunts
        $riddlesFromFinishedHunts = $entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT r.id)')
            ->from(ParticipateHunt::class, 'ph')
            ->join('ph.hunt', 'h')
            ->join('h.riddles', 'r')
            ->where('ph.hunter = :user')
            ->andWhere('ph.finished = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        // Use UNION to avoid counting the same riddle twice
        // (if a riddle was solved individually AND is part of a finished hunt)
        $riddleIds = $entityManager->createQueryBuilder()
            ->select('DISTINCT IDENTITY(pr.riddle) as riddleId')
            ->from(ParticipateRiddle::class, 'pr')
            ->where('pr.hunter = :user')
            ->andWhere('pr.finishTime IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $riddleIdsFromHunts = $entityManager->createQueryBuilder()
            ->select('DISTINCT r.id as riddleId')
            ->from(ParticipateHunt::class, 'ph')
            ->join('ph.hunt', 'h')
            ->join('h.riddles', 'r')
            ->where('ph.hunter = :user')
            ->andWhere('ph.finished = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        // Merge and get unique riddle IDs
        $allRiddleIds = [];
        foreach ($riddleIds as $row) {
            if ($row['riddleId']) {
                $allRiddleIds[$row['riddleId']] = true;
            }
        }
        foreach ($riddleIdsFromHunts as $row) {
            if ($row['riddleId']) {
                $allRiddleIds[$row['riddleId']] = true;
            }
        }

        return count($allRiddleIds);
    }

    /**
     * Calculate the total time a user has spent on hunts by summing all time from their ParticipateHunt entities.
     */
    public function getTotalTime(User $user): int
    {
        $result = $this->createQueryBuilder('u')
            ->select('COALESCE(SUM(ph.time), 0) as totalTime')
            ->leftJoin('u.participateHunts', 'ph')
            ->where('u = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }
}
