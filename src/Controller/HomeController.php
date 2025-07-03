<?php

namespace App\Controller;

use App\Repository\ServiceRepository;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\EmployeeRepository;
use App\Repository\AppointmentRepository;
use App\Entity\Appointment;
use App\Entity\Service;
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
        $appointmentDateTime = new \DateTime($data['appointment_date'] ?? '');
        $employeeId = $data['employee_id'] ?? null;

        if (!$appointmentDateTime || !$employeeId) {
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }

        $employee = $em->getRepository(\App\Entity\Employee::class)->find($employeeId);
        if (!$employee || !$employee->isAvailable() || !$employee->getUser()->isActive()) {
            return new JsonResponse(['error' => 'Employé non trouvé ou non disponible'], 404);
        }

        $startTime = clone $appointmentDateTime;
        $startTime->modify('-2 hours');
        $endTime = clone $appointmentDateTime;
        $endTime->modify('+2 hours');

        $existingAppointments = $appointmentRepository->createQueryBuilder('a')
            ->andWhere('a.employee = :employee')
            ->andWhere('a.appointmentDate BETWEEN :start AND :end')
            ->andWhere('a.status != :cancelled')
            ->setParameter('employee', $employee)
            ->setParameter('start', $startTime)
            ->setParameter('end', $endTime)
            ->setParameter('cancelled', Appointment::STATUS_CANCELLED)
            ->getQuery()
            ->getResult();

        if (!empty($existingAppointments)) {
            return new JsonResponse(['error' => 'Créneau non disponible'], 409);
        }

        $appointment = new Appointment();
        $appointment->setClient($this->getUser());
        $appointment->setService($service);
        $appointment->setEmployee($employee);
        $appointment->setAppointmentDate($appointmentDateTime);
        $appointment->setPrice($service->getPrice());
        $appointment->setStatus(Appointment::STATUS_PENDING);

        $em->persist($appointment);
        $em->flush();

        return new JsonResponse(['success' => 'Réservation créée avec succès', 'id' => $appointment->getId()]);
    }
} 