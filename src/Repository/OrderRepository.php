<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }
    public function getProductCount($year = null, $dateStart = null, $dateEnd = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('SUM(oi.quantity)')
            ->from('App\\Entity\\OrderItem', 'oi')
            ->join('oi.order', 'o');
        if ($dateStart && $dateEnd) {
            $qb->where('o.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $dateStart.' 00:00:00')
                ->setParameter('end', $dateEnd.' 23:59:59');
        } elseif ($year) {
            $qb->where('o.createdAt BETWEEN :startYear AND :endYear')
                ->setParameter('startYear', $year.'-01-01 00:00:00')
                ->setParameter('endYear', $year.'-12-31 23:59:59');
        }
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getTotalSales($year = null, $dateStart = null, $dateEnd = null): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.total)');
        if ($dateStart && $dateEnd) {
            $qb->where('o.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $dateStart.' 00:00:00')
                ->setParameter('end', $dateEnd.' 23:59:59');
        } elseif ($year) {
            $qb->where('o.createdAt BETWEEN :startYear AND :endYear')
                ->setParameter('startYear', $year.'-01-01 00:00:00')
                ->setParameter('endYear', $year.'-12-31 23:59:59');
        }
        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function getOrderCount($year = null, $dateStart = null, $dateEnd = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)');
        if ($dateStart && $dateEnd) {
            $qb->where('o.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $dateStart.' 00:00:00')
                ->setParameter('end', $dateEnd.' 23:59:59');
        } elseif ($year) {
            $qb->where('o.createdAt BETWEEN :startYear AND :endYear')
                ->setParameter('startYear', $year.'-01-01 00:00:00')
                ->setParameter('endYear', $year.'-12-31 23:59:59');
        }
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    /**
     * Retourne les statistiques de ventes par mois et année
     * @return array
     */
    public function getMonthlyStats($year = null, $dateStart = null, $dateEnd = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $params = [];
        $where = [];
        if ($dateStart && $dateEnd) {
            $where[] = 'created_at BETWEEN :start AND :end';
            $params['start'] = $dateStart.' 00:00:00';
            $params['end'] = $dateEnd.' 23:59:59';
        } elseif ($year) {
            $where[] = 'YEAR(created_at) = :year';
            $params['year'] = $year;
        }
        $sql = 'SELECT 
            MONTH(created_at) AS month, 
            YEAR(created_at) AS year, 
            COUNT(id) AS orders, 
            SUM(total) AS total
        FROM `order`';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY year, month ORDER BY year DESC, month DESC';
        $results = $conn->executeQuery($sql, $params)->fetchAllAssociative();
        $mois = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];
        foreach ($results as &$row) {
            $row['month'] = $mois[(int)$row['month']] ?? $row['month'];
        }
        return $results;
    }

    /**
     * Retourne la liste des années où il y a des commandes
     */
    public function getAvailableYears(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT DISTINCT YEAR(created_at) as year FROM `order` ORDER BY year DESC';
        $results = $conn->executeQuery($sql)->fetchAllAssociative();
        return array_column($results, 'year');
    }
    /**
     * Retourne le nombre de commandes livrées et annulées (optionnellement pour une année)
     * @return array ['delivered' => int, 'cancelled' => int, 'total' => int]
     */
    public function getOrderStatusStats($year = null, $dateStart = null, $dateEnd = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $params = [];
        $where = [];
        if ($dateStart && $dateEnd) {
            $where[] = 'created_at BETWEEN :start AND :end';
            $params['start'] = $dateStart.' 00:00:00';
            $params['end'] = $dateEnd.' 23:59:59';
        } elseif ($year) {
            $where[] = 'YEAR(created_at) = :year';
            $params['year'] = $year;
        }
        $sql = 'SELECT status, COUNT(id) as nb FROM `order`';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY status';
        $results = $conn->executeQuery($sql, $params)->fetchAllAssociative();
        $stats = ['delivered' => 0, 'cancelled' => 0, 'total' => 0];
        foreach ($results as $row) {
            if (strtolower($row['status']) === 'livrée' || strtolower($row['status']) === 'livree') {
                $stats['delivered'] += (int)$row['nb'];
            } elseif (strtolower($row['status']) === 'annulée' || strtolower($row['status']) === 'annulee') {
                $stats['cancelled'] += (int)$row['nb'];
            }
            $stats['total'] += (int)$row['nb'];
        }
        return $stats;
    }
}
