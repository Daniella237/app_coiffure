<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\AppointmentRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/', name: 'admin_users')]
    public function index(UserRepository $userRepository, AppointmentRepository $appointmentRepository, Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $search = $request->query->get('search');

        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        if ($search) {
            $queryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        $users = $queryBuilder->setFirstResult(($page - 1) * $limit)
                             ->setMaxResults($limit)
                             ->getQuery()
                             ->getResult();

        // Calculer le montant total des rendez-vous pour chaque utilisateur
        foreach ($users as $user) {
            $totalSpent = $appointmentRepository->getTotalSpentByUser($user);
            $user->totalSpent = $totalSpent;
        }

        // Calculer le nombre total d'utilisateurs avec le même filtre de recherche
        $countQueryBuilder = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)');
        
        if ($search) {
            $countQueryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                             ->setParameter('search', '%' . $search . '%');
        }
        
        $totalUsers = $countQueryBuilder->getQuery()->getSingleScalarResult();
        $totalPages = ceil($totalUsers / $limit);

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_show')]
    public function show(
        User $user,
        AppointmentRepository $appointmentRepository,
        OrderRepository $orderRepository
    ): Response {
        // Historique des rendez-vous
        $appointments = $appointmentRepository->findBy(['client' => $user], ['appointmentDate' => 'DESC'], 10);
        
        // Historique des commandes
        $orders = $orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC'], 10);
        
        // Calcul du total des prestations complétées
        $totalPrestations = $appointmentRepository->getTotalSpentByUserCompleted($user);
        
        // Calcul du total des commandes livrées
        $totalOrders = $orderRepository->getTotalSpentByUserDelivered($user);
        
        // Total général (prestations + commandes)
        $totalGeneral = $totalPrestations + $totalOrders;
        
        // Statistiques du client
        $stats = [
            'total_appointments' => $appointmentRepository->count(['client' => $user]),
            'total_orders' => $orderRepository->count(['user' => $user]),
            'total_spent' => $totalGeneral,
            'total_prestations' => $totalPrestations,
            'total_orders_amount' => $totalOrders,
            'average_order' => $orderRepository->getAverageOrderByUser($user),
        ];

        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'appointments' => $appointments,
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        $userEmail = $user->getEmail();
        
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', "Compte utilisateur {$userEmail} supprimé avec succès !");
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/{id}/toggle-status', name: 'admin_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

        $status = $user->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Compte utilisateur {$user->getEmail()} {$status} avec succès !");
        
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }
} 