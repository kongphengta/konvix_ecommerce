<?php

namespace App\Repository;

use App\Entity\CodePromo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CodePromoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodePromo::class);
    }

    public function findValidByCode(string $code): ?CodePromo
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('c')
            ->andWhere('c.code = :code')
            ->andWhere('c.actif = true')
            ->andWhere('(c.dateDebut IS NULL OR c.dateDebut <= :now)')
            ->andWhere('(c.dateFin IS NULL OR c.dateFin >= :now)')
            ->setParameter('code', strtoupper($code))
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
