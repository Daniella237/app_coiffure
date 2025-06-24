<?php

namespace App\Repository;

use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    public function findBestSellingProducts(int $limit = 10): array
    {
        return $this->createQueryBuilder('oi')
            ->select('p.name, p.id, SUM(oi.quantity) as totalSold')
            ->join('oi.product', 'p')
            ->join('oi.order', 'o')
            ->andWhere('o.status != :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->groupBy('p.id')
            ->orderBy('totalSold', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
} 