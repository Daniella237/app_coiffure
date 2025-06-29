<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'p')
            ->addSelect('oi', 'p')
            ->andWhere('o.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentOrders(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.user', 'u')
            ->addSelect('u')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.user', 'u')
            ->addSelect('u')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalSales(\DateTime $from = null, \DateTime $to = null): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->andWhere('o.status != :cancelled')
            ->setParameter('cancelled', Order::STATUS_CANCELLED);

        if ($from) {
            $qb->andWhere('o.createdAt >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('o.createdAt <= :to')
               ->setParameter('to', $to);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function getOrdersCount(\DateTime $from = null, \DateTime $to = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.status != :cancelled')
            ->setParameter('cancelled', Order::STATUS_CANCELLED);

        if ($from) {
            $qb->andWhere('o.createdAt >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('o.createdAt <= :to')
               ->setParameter('to', $to);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getAverageCartValue(): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('AVG(o.totalAmount) as average')
            ->where('o.status = :delivered')
            ->setParameter('delivered', 'delivered');

        $result = $qb->getQuery()->getSingleResult();
        return $result['average'] ? (float) $result['average'] : 0.0;
    }

    public function getTotalRevenue(): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount) as total')
            ->where('o.status = :delivered')
            ->setParameter('delivered', 'delivered');

        $result = $qb->getQuery()->getSingleResult();
        return $result['total'] ? (float) $result['total'] : 0.0;
    }

    public function getTotalSpentByUser(User $user): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount) as total')
            ->where('o.user = :user')
            ->andWhere('o.status = :delivered')
            ->setParameter('user', $user)
            ->setParameter('delivered', 'delivered');

        $result = $qb->getQuery()->getSingleResult();
        return $result['total'] ? (float) $result['total'] : 0.0;
    }

    public function getAverageOrderByUser(User $user): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('AVG(o.totalAmount) as average')
            ->where('o.user = :user')
            ->andWhere('o.status = :delivered')
            ->setParameter('user', $user)
            ->setParameter('delivered', 'delivered');

        $result = $qb->getQuery()->getSingleResult();
        return $result['average'] ? (float) $result['average'] : 0.0;
    }

    public function getTotalSpentByUserDelivered(User $user): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount) as total')
            ->where('o.user = :user')
            ->andWhere('o.status = :delivered')
            ->setParameter('user', $user)
            ->setParameter('delivered', 'delivered');

        $result = $qb->getQuery()->getSingleResult();
        return $result['total'] ? (float) $result['total'] : 0.0;
    }
} 
