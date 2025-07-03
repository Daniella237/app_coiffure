<?php

namespace App\Controller;

use App\Repository\ServiceRepository;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\EmployeeRepository;
use App\Repository\AppointmentRepository;
use App\Repository\CartItemRepository;
use App\Entity\Appointment;
use App\Entity\Service;
use App\Entity\Product;
use App\Entity\CartItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        ServiceRepository $serviceRepository,
        ProductRepository $productRepository
    ): Response {
        $services = $serviceRepository->findAll();
        $user = $this->getUser();
        $products = $productRepository->findAll();
        return $this->render('home/index.html.twig', [
            'services' => $services,
            'products' => $products,
            'user' => $user,
        ]);
    }

    #[Route('/services', name: 'services')]
    public function services(
        Request $request,
        ServiceRepository $serviceRepository,
        ServiceCategoryRepository $categoryRepository,
        ProductRepository $productRepository
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;
        $categoryId = $request->query->get('category');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        $sortBy = $request->query->get('sort', 'name');
        $sortOrder = $request->query->get('order', 'asc');

        $queryBuilder = $serviceRepository->createQueryBuilder('s')
            ->join('s.category', 'c')
            ->andWhere('s.isActive = :active')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true);

        if ($categoryId) {
            $queryBuilder->andWhere('s.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($minPrice) {
            $queryBuilder->andWhere('s.price >= :minPrice')
                ->setParameter('minPrice', $minPrice);
        }
        if ($maxPrice) {
            $queryBuilder->andWhere('s.price <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        switch ($sortBy) {
            case 'price':
                $queryBuilder->orderBy('s.price', $sortOrder);
                break;
            case 'duration':
                $queryBuilder->orderBy('s.durationMinutes', $sortOrder);
                break;
            case 'name':
            default:
                $queryBuilder->orderBy('s.name', $sortOrder);
                break;
        }

        $totalResults = $queryBuilder->getQuery()->getResult();
        $totalPages = ceil(count($totalResults) / $limit);

        $services = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $categories = $categoryRepository->findActiveCategories();
        $products = $productRepository->findAll();
        $services = $serviceRepository->findAll();
        return $this->render('home/services.html.twig', [
            'services' => $services,
            'categories' => $categories,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'filters' => [
                'category' => $categoryId,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'sort' => $sortBy,
                'order' => $sortOrder,
            ],
            'products' => $products,
            'services' => $services,
        ]);
    }

    #[Route('/services/{id}', name: 'service_detail', requirements: ['id' => '\d+'])]
    public function serviceDetail(
        Service $service,
        EmployeeRepository $employeeRepository,
        ProductRepository $productRepository,
        ServiceRepository $serviceRepository
    ): Response {
        if (!$service->isActive() || !$service->getCategory()->isActive()) {
            throw $this->createNotFoundException('Service non trouvé');
        }

        $employees = $employeeRepository->createQueryBuilder('e')
            ->join('e.employeeServices', 'es')
            ->join('e.user', 'u')
            ->andWhere('es.service = :service')
            ->andWhere('e.isAvailable = :available')
            ->andWhere('u.isActive = :active')
            ->setParameter('service', $service)
            ->setParameter('available', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $products = $productRepository->findAll();
        $services = $serviceRepository->findAll();
        return $this->render('home/service_detail.html.twig', [
            'service' => $service,
            'products' => $products,
            'services' => $services,
            'employees' => $employees,
        ]);
    }

    #[Route('/services/{id}/book', name: 'service_book', methods: ['POST'])]
    public function bookService(
        Service $service,
        Request $request,
        EntityManagerInterface $em,
        AppointmentRepository $appointmentRepository
    ): JsonResponse {
        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour réserver'], 401);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Données JSON invalides'], 400);
        }

        try {
            $appointmentDateTime = new \DateTime($data['appointment_date'] ?? '');
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Format de date invalide'], 400);
        }

        if (!$appointmentDateTime) {
            return new JsonResponse(['error' => 'Date de rendez-vous requise'], 400);
        }

        // Vérifier que la date n'est pas dans le passé
        $now = new \DateTime();
        if ($appointmentDateTime < $now) {
            return new JsonResponse(['error' => 'Impossible de réserver dans le passé'], 400);
        }

        // Vérifier si le service est actif
        if (!$service->isActive() || !$service->getCategory()->isActive()) {
            return new JsonResponse(['error' => 'Service non disponible'], 404);
        }

        // Créer la réservation sans employé assigné (l'admin l'assignera plus tard)
        $appointment = new Appointment();
        $appointment->setClient($this->getUser());
        $appointment->setService($service);
        $appointment->setEmployee(null); // Pas d'employé assigné pour le moment
        $appointment->setAppointmentDate($appointmentDateTime);
        $appointment->setPrice($service->getPrice());
        $appointment->setStatus(Appointment::STATUS_PENDING);
        
        if (isset($data['notes']) && !empty($data['notes'])) {
            $appointment->setNotes($data['notes']);
        }

        $em->persist($appointment);
        $em->flush();

        return new JsonResponse([
            'success' => 'Réservation créée avec succès ! L\'admin vous assignera un employé prochainement.',
            'id' => $appointment->getId(),
            'appointment_date' => $appointmentDateTime->format('Y-m-d H:i:s'),
            'service_name' => $service->getName(),
            'price' => $service->getPrice()
        ]);
    }

    #[Route('/products', name: 'products')]
    public function products(
        Request $request,
        ProductRepository $productRepository,
        ProductCategoryRepository $categoryRepository,
        ServiceRepository $serviceRepository
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;
        $categoryId = $request->query->get('category');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        $sortBy = $request->query->get('sort', 'name');
        $sortOrder = $request->query->get('order', 'asc');
        $inStock = $request->query->get('in_stock');

        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->andWhere('p.isActive = :active')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true);

        if ($categoryId) {
            $queryBuilder->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($minPrice) {
            $queryBuilder->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', $minPrice);
        }
        if ($maxPrice) {
            $queryBuilder->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        if ($inStock) {
            $queryBuilder->andWhere('p.stockQuantity > 0');
        }

        switch ($sortBy) {
            case 'price':
                $queryBuilder->orderBy('p.price', $sortOrder);
                break;
            case 'stock':
                $queryBuilder->orderBy('p.stockQuantity', $sortOrder);
                break;
            case 'name':
            default:
                $queryBuilder->orderBy('p.name', $sortOrder);
                break;
        }

        $totalResults = $queryBuilder->getQuery()->getResult();
        $totalPages = ceil(count($totalResults) / $limit);
        $services = $serviceRepository->findAll();

        $products = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $categories = $categoryRepository->createQueryBuilder('c')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('home/products.html.twig', [
            'products' => $products,
            'services' => $services,
            'categories' => $categories,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'filters' => [
                'category' => $categoryId,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'sort' => $sortBy,
                'order' => $sortOrder,
                'in_stock' => $inStock,
            ],
        ]);
    }

    #[Route('/products/{id}', name: 'product_detail', requirements: ['id' => '\d+'])]
    public function productDetail(
        Product $product,
        ProductRepository $productRepository,
        ServiceRepository $serviceRepository,
    ): Response {
        if (!$product->isActive() || !$product->getCategory()->isActive()) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        // Produits similaires de la même catégorie
        $similarProducts = $productRepository->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->andWhere('p.category = :category')
            ->andWhere('p.id != :currentProduct')
            ->andWhere('p.isActive = :active')
            ->andWhere('c.isActive = :active')
            ->setParameter('category', $product->getCategory())
            ->setParameter('currentProduct', $product->getId())
            ->setParameter('active', true)
            ->setMaxResults(4)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        $services = $serviceRepository->findAll();
        $products = $productRepository->findAll();
        return $this->render('home/product_detail.html.twig', [
            'product' => $product,
            'similarProducts' => $similarProducts,
            'services' => $services,
            'products' => $products,
        ]);
    }

    #[Route('/products/{id}/add-to-cart', name: 'product_add_to_cart', methods: ['POST'])]
    public function addToCart(
        Product $product,
        Request $request,
        EntityManagerInterface $em,
        CartItemRepository $cartItemRepository
    ): JsonResponse {
        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour ajouter au panier'], 401);
        }

        if (!$product->isActive() || !$product->getCategory()->isActive()) {
            return new JsonResponse(['error' => 'Produit non disponible'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $quantity = $data['quantity'] ?? 1;

        if ($quantity <= 0) {
            return new JsonResponse(['error' => 'Quantité invalide'], 400);
        }

        if ($product->getStockQuantity() < $quantity) {
            return new JsonResponse(['error' => 'Stock insuffisant'], 400);
        }

        // Vérifier si le produit est déjà dans le panier
        $existingCartItem = $cartItemRepository->findOneBy([
            'user' => $this->getUser(),
            'product' => $product
        ]);

        if ($existingCartItem) {
            $newQuantity = $existingCartItem->getQuantity() + $quantity;
            if ($product->getStockQuantity() < $newQuantity) {
                return new JsonResponse(['error' => 'Stock insuffisant pour cette quantité totale'], 400);
            }
            $existingCartItem->setQuantity($newQuantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setUser($this->getUser());
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $em->persist($cartItem);
        }

        $em->flush();

        return new JsonResponse([
            'success' => 'Produit ajouté au panier',
            'quantity' => $quantity,
            'product_name' => $product->getName()
        ]);
    }
} 