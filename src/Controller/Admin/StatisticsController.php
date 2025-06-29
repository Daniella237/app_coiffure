<?php

namespace App\Controller\Admin;

use App\Repository\AppointmentRepository;
use App\Repository\OrderRepository;
use App\Repository\ServiceRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/statistics')]
#[IsGranted('ROLE_ADMIN')]
class StatisticsController extends AbstractController
{
    #[Route('/', name: 'admin_statistics')]
    public function index(
        AppointmentRepository $appointmentRepository,
        OrderRepository $orderRepository,
        ServiceRepository $serviceRepository,
        ProductRepository $productRepository
    ): Response {
        // Statistiques des rendez-vous par semaine (4 dernières semaines)
        $weeklyAppointments = $appointmentRepository->getWeeklyAppointments(4);
        
        // Panier moyen des clients
        $averageCart = $orderRepository->getAverageCartValue();
        
        // Prestations les plus réservées
        $topServices = $serviceRepository->getMostBookedServices(10);
        
        // Produits les plus vendus
        $topProducts = $productRepository->getMostSoldProducts(10);
        
        // Calcul du chiffre d'affaires des réservations terminées
        $appointmentsRevenue = $appointmentRepository->getTotalRevenueFromCompleted();
        
        // Calcul du chiffre d'affaires des commandes livrées
        $ordersRevenue = $orderRepository->getTotalRevenue();
        
        // Total du chiffre d'affaires
        $totalRevenue = $appointmentsRevenue + $ordersRevenue;
        
        // Statistiques générales
        $stats = [
            'total_appointments' => $appointmentRepository->count([]),
            'total_orders' => $orderRepository->count([]),
            'total_revenue' => $totalRevenue,
            'appointments_revenue' => $appointmentsRevenue,
            'orders_revenue' => $ordersRevenue,
            'active_services' => $serviceRepository->count(['isActive' => true]),
            'active_products' => $productRepository->count(['isActive' => true]),
        ];

        return $this->render('admin/statistics/index.html.twig', [
            'weeklyAppointments' => $weeklyAppointments,
            'averageCart' => $averageCart,
            'topServices' => $topServices,
            'topProducts' => $topProducts,
            'stats' => $stats,
        ]);
    }
} 
 