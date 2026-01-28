<?php

namespace App\Repository;

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
     * Retourne le nombre de nouveaux clients par mois et année
     * @return array [month, year, total]
     */
    public function getNewClientsByMonth($year = null, $dateStart = null, $dateEnd = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $params = [];
        $where = [];
        if ($dateStart && $dateEnd) {
            $where[] = 'registered_at BETWEEN :start AND :end';
            $params['start'] = $dateStart.' 00:00:00';
            $params['end'] = $dateEnd.' 23:59:59';
        } elseif ($year) {
            $where[] = 'YEAR(registered_at) = :year';
            $params['year'] = $year;
        }
        $sql = 'SELECT MONTH(registered_at) AS month, YEAR(registered_at) AS year, COUNT(id) AS total
            FROM user';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY year, month ORDER BY year DESC, month DESC';
        $mois = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];
        $results = $conn->executeQuery($sql, $params)->fetchAllAssociative();
        foreach ($results as &$row) {
            $row['month'] = $mois[(int)$row['month']] ?? $row['month'];
        }
        return $results;
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
