<?php

namespace App\Repository;

use App\Entity\Coupon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CouponRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coupon::class);
    }

    public function findByCode(string $code): ?Coupon
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.code = :code')
            ->andWhere('c.isActive = true')
            ->setParameter('code', strtoupper($code))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveBySeller($seller)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c. seller = :seller')
            ->andWhere('c.isActive = true')
            ->setParameter('seller', $seller)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}