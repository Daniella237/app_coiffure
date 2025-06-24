<?php

namespace App\Repository;

use App\Entity\Employee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EmployeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    public function findAvailableEmployees(): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.user', 'u')
            ->andWhere('e.isAvailable = :available')
            ->andWhere('u.isActive = :active')
            ->setParameter('available', true)
            ->setParameter('active', true)
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findEmployeesByService(int $serviceId): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.employeeServices', 'es')
            ->join('e.user', 'u')
            ->andWhere('es.service = :serviceId')
            ->andWhere('e.isAvailable = :available')
            ->andWhere('u.isActive = :active')
            ->setParameter('serviceId', $serviceId)
            ->setParameter('available', true)
            ->setParameter('active', true)
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findEmployeeWithUser(int $id): ?Employee
    {
        return $this->createQueryBuilder('e')
            ->join('e.user', 'u')
            ->addSelect('u')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 