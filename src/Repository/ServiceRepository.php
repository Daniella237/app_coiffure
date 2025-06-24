<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function findActiveServices(): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.category', 'c')
            ->andWhere('s.isActive = :active')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.category = :categoryId')
            ->andWhere('s.isActive = :active')
            ->setParameter('categoryId', $categoryId)
            ->setParameter('active', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findServicesByEmployee(int $employeeId): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.employeeServices', 'es')
            ->andWhere('es.employee = :employeeId')
            ->andWhere('s.isActive = :active')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('active', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 