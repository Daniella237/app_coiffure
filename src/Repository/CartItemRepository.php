<?php

namespace App\Repository;

use App\Entity\CartItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('ci')
            ->join('ci.product', 'p')
            ->join('p.category', 'c')
            ->addSelect('p', 'c')
            ->andWhere('ci.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ci.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalItemsInCart(int $userId): int
    {
        return $this->createQueryBuilder('ci')
            ->select('SUM(ci.quantity)')
            ->andWhere('ci.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function clearUserCart(int $userId): void
    {
        $this->createQueryBuilder('ci')
            ->delete()
            ->andWhere('ci.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }
} 