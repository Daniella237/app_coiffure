<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findActiveProducts(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->andWhere('p.isActive = :active')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.category = :categoryId')
            ->andWhere('p.isActive = :active')
            ->setParameter('categoryId', $categoryId)
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findInStock(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->andWhere('p.isActive = :active')
            ->andWhere('c.isActive = :active')
            ->andWhere('p.stockQuantity > 0')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.sku = :sku')
            ->setParameter('sku', $sku)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLowStock(int $threshold = 5): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.stockQuantity <= :threshold')
            ->setParameter('active', true)
            ->setParameter('threshold', $threshold)
            ->orderBy('p.stockQuantity', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMostSoldProducts(int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.name, SUM(oi.quantity) as salesCount')
            ->leftJoin('p.orderItems', 'oi')
            ->leftJoin('oi.order', 'o')
            ->where('o.status = :delivered')
            ->setParameter('delivered', 'delivered')
            ->groupBy('p.id, p.name')
            ->orderBy('salesCount', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getProductCategoriesWithProducts(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('pc', 'p')
            ->join('p.category', 'pc')
            ->where('pc.isActive = :active')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('pc.name', 'ASC')
            ->addOrderBy('p.name', 'ASC');

        $results = $qb->getQuery()->getResult();
        
        // Organiser par catégorie
        $categories = [];
        foreach ($results as $result) {
            if ($result instanceof ProductCategory) {
                $categories[$result->getId()] = [
                    'category' => $result,
                    'products' => []
                ];
            } elseif ($result instanceof Product) {
                $categoryId = $result->getCategory()->getId();
                if (isset($categories[$categoryId])) {
                    $categories[$categoryId]['products'][] = $result;
                }
            }
        }
        
        return array_values($categories);
    }
} 