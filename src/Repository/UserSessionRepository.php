<?php

namespace App\Repository;

use App\Entity\UserSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSession::class);
    }

    public function findActiveSessionsByUser(int $userId): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('us')
            ->andWhere('us.user = :userId')
            ->andWhere('us.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', $now)
            ->orderBy('us.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByToken(string $token): ?UserSession
    {
        return $this->createQueryBuilder('us')
            ->join('us.user', 'u')
            ->addSelect('u')
            ->andWhere('us.sessionToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function cleanExpiredSessions(): void
    {
        $now = new \DateTime();
        
        $this->createQueryBuilder('us')
            ->delete()
            ->andWhere('us.expiresAt <= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }

    public function revokeUserSessions(int $userId, string $excludeToken = null): void
    {
        $qb = $this->createQueryBuilder('us')
            ->delete()
            ->andWhere('us.user = :userId')
            ->setParameter('userId', $userId);

        if ($excludeToken) {
            $qb->andWhere('us.sessionToken != :excludeToken')
               ->setParameter('excludeToken', $excludeToken);
        }

        $qb->getQuery()->execute();
    }
} 