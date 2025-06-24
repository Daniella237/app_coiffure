<?php

namespace App\Repository;

use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    public function findActivePromotions(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.startDate <= :now')
            ->andWhere('p.endDate >= :now')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->orderBy('p.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code): ?Promotion
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findValidPromotion(string $code): ?Promotion
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.code = :code')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.startDate <= :now')
            ->andWhere('p.endDate >= :now')
            ->andWhere('(p.usageLimit IS NULL OR p.usageCount < p.usageLimit)')
            ->setParameter('code', $code)
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 