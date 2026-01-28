<?php

namespace App\Repository;

use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderItem>
 */

class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    /**
     * Retourne le top 5 des produits les plus vendus (par quantité)
     * @return array [product_name, total_quantity]
     */
    public function getTopProducts(int $limit = 5, $year = null, $dateStart = null, $dateEnd = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $limit = (int) $limit;
        $params = [];
        $where = [];
        if ($dateStart && $dateEnd) {
            $where[] = 'o.created_at BETWEEN :start AND :end';
            $params['start'] = $dateStart.' 00:00:00';
            $params['end'] = $dateEnd.' 23:59:59';
        } elseif ($year) {
            $where[] = 'YEAR(o.created_at) = :year';
            $params['year'] = $year;
        }
        $sql = 'SELECT p.name, SUM(oi.quantity) as total_quantity
            FROM order_item oi
            JOIN product p ON oi.product_id = p.id
            JOIN `order` o ON oi.order_id = o.id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY p.id, p.name
            ORDER BY total_quantity DESC
            LIMIT ' . $limit;
        $result = $conn->executeQuery($sql, $params);
        return $result->fetchAllAssociative();
    }

    //    /**
    //     * @return OrderItem[] Returns an array of OrderItem objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?OrderItem
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
