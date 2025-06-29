<?php

namespace App\Repository;

use App\Entity\Service;
use App\Entity\ServiceCategory;
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

    public function getMostBookedServices(int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.name, COUNT(a.id) as bookingCount')
            ->leftJoin('s.appointments', 'a')
            ->groupBy('s.id, s.name')
            ->orderBy('bookingCount', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getServiceCategoriesWithServices(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('sc', 's')
            ->join('s.category', 'sc')
            ->where('sc.isActive = :active')
            ->andWhere('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('sc.name', 'ASC')
            ->addOrderBy('s.name', 'ASC');

        $results = $qb->getQuery()->getResult();
        
        // Organiser par catégorie
        $categories = [];
        foreach ($results as $result) {
            if ($result instanceof ServiceCategory) {
                $categories[$result->getId()] = [
                    'category' => $result,
                    'services' => []
                ];
            } elseif ($result instanceof Service) {
                $categoryId = $result->getCategory()->getId();
                if (isset($categories[$categoryId])) {
                    $categories[$categoryId]['services'][] = $result;
                }
            }
        }
        
        return array_values($categories);
    }
} 