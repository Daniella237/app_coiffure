<?php

namespace App\Repository;

use App\Entity\ServiceCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiceCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceCategory::class);
    }

    public function findActiveCategories(): array
    {
        return $this->createQueryBuilder('sc')
            ->andWhere('sc.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('sc.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCategoryWithServices(int $id): ?ServiceCategory
    {
        return $this->createQueryBuilder('sc')
            ->leftJoin('sc.services', 's')
            ->addSelect('s')
            ->andWhere('sc.id = :id')
            ->andWhere('sc.isActive = :active')
            ->setParameter('id', $id)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 