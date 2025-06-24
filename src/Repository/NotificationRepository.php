<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findByUser(int $userId, bool $unreadOnly = false): array
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.user = :userId')
            ->setParameter('userId', $userId);

        if ($unreadOnly) {
            $qb->andWhere('n.isRead = :read')
               ->setParameter('read', false);
        }

        return $qb->orderBy('n.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function countUnreadNotifications(int $userId): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :userId')
            ->andWhere('n.isRead = :read')
            ->setParameter('userId', $userId)
            ->setParameter('read', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllAsRead(int $userId): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':read')
            ->andWhere('n.user = :userId')
            ->setParameter('read', true)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.user', 'u')
            ->addSelect('u')
            ->andWhere('n.type = :type')
            ->setParameter('type', $type)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 