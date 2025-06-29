<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\ServiceRepository;
use App\Repository\ProductRepository;
use App\Repository\EmployeeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(
        AppointmentRepository $appointmentRepository,
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        ServiceRepository $serviceRepository,
        ProductRepository $productRepository,
        EmployeeRepository $employeeRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Date d'aujourd'hui
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');

        // Rendez-vous du jour
        $todayAppointments = $appointmentRepository->createQueryBuilder('a')
            ->where('a.appointmentDate >= :today')
            ->andWhere('a.appointmentDate < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('a.appointmentDate', 'ASC')
            ->getQuery()
            ->getResult();

        // Chiffre d'affaires du mois
        $monthStart = new \DateTime('first day of this month');
        $monthStart->setTime(0, 0, 0);
        $monthEnd = new \DateTime('first day of next month');
        $monthEnd->setTime(0, 0, 0);

        $monthlyRevenue = $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.status IN (:statuses)')
            ->andWhere('o.createdAt >= :monthStart')
            ->andWhere('o.createdAt < :monthEnd')
            ->setParameter('statuses', ['delivered', 'shipped'])
            ->setParameter('monthStart', $monthStart)
            ->setParameter('monthEnd', $monthEnd)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Commandes à traiter (pending et processing)
        $pendingOrders = $orderRepository->createQueryBuilder('o')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', ['pending', 'processing'])
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Prochains créneaux réservés (rendez-vous futurs)
        $upcomingAppointments = $appointmentRepository->createQueryBuilder('a')
            ->leftJoin('a.client', 'c')
            ->leftJoin('a.service', 's')
            ->addSelect('c', 's')
            ->where('a.appointmentDate > :now')
            ->andWhere('a.status IN (:statuses)')
            ->setParameter('now', new \DateTime())
            ->setParameter('statuses', ['pending', 'confirmed'])
            ->orderBy('a.appointmentDate', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Dernières commandes (pour l'affichage)
        $recentOrders = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Statistiques générales
        $connection = $entityManager->getConnection();
        $activeUsers = $connection->executeQuery(
            "SELECT COUNT(*) FROM users WHERE roles::text LIKE :role",
            ['role' => '%ROLE_USER%']
        )->fetchOne();

        $totalAppointments = $appointmentRepository->count([]);
        $totalOrders = $orderRepository->count([]);
        $totalEmployees = $employeeRepository->count([]);
        $activeServices = $serviceRepository->count(['isActive' => true]);
        $activeProducts = $productRepository->count(['isActive' => true]);

        // Employés disponibles
        $availableEmployees = $employeeRepository->count(['isAvailable' => true]);

        return $this->render('admin/dashboard.html.twig', [
            'todayAppointments' => $todayAppointments,
            'monthlyRevenue' => $monthlyRevenue,
            'activeUsers' => $activeUsers,
            'availableEmployees' => $availableEmployees,
            'upcomingAppointments' => $upcomingAppointments,
            'recentOrders' => $recentOrders,
            'pendingOrders' => $pendingOrders,
            'totalAppointments' => $totalAppointments,
            'totalOrders' => $totalOrders,
            'totalEmployees' => $totalEmployees,
            'activeServices' => $activeServices,
            'activeProducts' => $activeProducts,
        ]);
    }
} 