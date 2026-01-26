<?php

namespace App\Repository;

use App\Entity\CodePromoUsage;
use App\Entity\User;
use App\Entity\CodePromo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CodePromoUsageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodePromoUsage::class);
    }

    public function hasUserUsedCodePromo(User $user, CodePromo $codePromo): bool
    {
        return (bool) $this->createQueryBuilder('u')
            ->andWhere('u.user = :user')
            ->andWhere('u.codePromo = :codePromo')
            ->setParameter('user', $user)
            ->setParameter('codePromo', $codePromo)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
