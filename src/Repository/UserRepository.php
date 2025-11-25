<?php

namespace App\Repository;

use App\Entity\DesignerTeam;
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
     * @param User[]|null $usersToExclude
     *
     * @return User[]
     */
    public function searchUsersByQuery(string $query, ?array $usersToExclude = null): array
    {
        $query = $this->createQueryBuilder('u')
            ->where('UPPER(u.nickname) LIKE UPPER(:query) OR UPPER(u.firstname) LIKE UPPER(:query) OR UPPER(u.lastname) LIKE UPPER(:query)')
            ->andWhere('u.activated = true')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults(10);

        if ($usersToExclude) {
            $query->andWhere('u NOT IN (:excludedUsers)')
                ->setParameter('excludedUsers', $usersToExclude);
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
}
