<?php

namespace App\Repository;

use App\Entity\Seller;
use App\Entity\SellerEarning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SellerEarningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SellerEarning::class);
    }

    public function findPendingBySeller(Seller $seller): array
    {
        return $this->createQueryBuilder('se')
            ->where('se.seller = :seller')
            ->andWhere('se.status = :status')
            ->setParameter('seller', $seller)
            ->setParameter('status', 'pending')
            ->orderBy('se.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function getTotalPendingBySeller(Seller $seller): float
    {
        $result = $this->createQueryBuilder('se')
            ->select('SUM(se.netAmount)')
            ->where('se.seller = :seller')
            ->andWhere('se.status = :status')
            ->setParameter('seller', $seller)
            ->setParameter('status', 'pending')
            ->getQuery()->getSingleScalarResult();
        return (float) ($result ?? 0.0);
    }

    public function findAllPendingGroupedBySeller(): array
    {
        // Récupère tous les gains pending avec leur vendeur
        $earnings = $this->createQueryBuilder('se')
            ->join('se.seller', 's')
            ->where('se.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery()->getResult();

        // Regroupe manuellement par vendeur
        $groups = [];
        foreach ($earnings as $earning) {
            $seller = $earning->getSeller();
            $id = $seller->getId();
            if (!isset($groups[$id])) {
                $groups[$id] = [
                    'seller' => $seller,
                    'grossTotal' => 0.0,
                    'commissionTotal' => 0.0,
                    'netTotal' => 0.0,
                    'count' => 0,
                ];
            }
            $groups[$id]['grossTotal'] += $earning->getGrossAmount();
            $groups[$id]['commissionTotal'] += $earning->getCommissionAmount();
            $groups[$id]['netTotal'] += $earning->getNetAmount();
            $groups[$id]['count']++;
        }

        // Arrondit les totaux
        foreach ($groups as &$g) {
            $g['grossTotal'] = round($g['grossTotal'], 2);
            $g['commissionTotal'] = round($g['commissionTotal'], 2);
            $g['netTotal'] = round($g['netTotal'], 2);
        }

        return array_values($groups);
    }
}
