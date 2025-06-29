<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/orders')]
#[IsGranted('ROLE_ADMIN')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'admin_orders')]
    public function index(OrderRepository $orderRepository, Request $request): Response
    {
        $status = $request->query->get('status');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;

        $queryBuilder = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->leftJoin('o.orderItems', 'i')
            ->addSelect('i')
            ->orderBy('o.createdAt', 'DESC');

        if ($status && $status !== 'all') {
            $queryBuilder->andWhere('o.status = :status')
                        ->setParameter('status', $status);
        }

        $orders = $queryBuilder->setFirstResult(($page - 1) * $limit)
                              ->setMaxResults($limit)
                              ->getQuery()
                              ->getResult();

        $totalOrders = $orderRepository->count($status ? ['status' => $status] : []);
        $totalPages = ceil($totalOrders / $limit);

        // Statistiques par statut
        $stats = [
            'pending' => $orderRepository->count(['status' => 'pending']),
            'processing' => $orderRepository->count(['status' => 'processing']),
            'shipped' => $orderRepository->count(['status' => 'shipped']),
            'delivered' => $orderRepository->count(['status' => 'delivered']),
            'cancelled' => $orderRepository->count(['status' => 'cancelled']),
        ];

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders,
            'currentStatus' => $status,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}', name: 'admin_order_show')]
    public function show(Order $order): Response
    {
        return $this->render('admin/orders/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/update-status', name: 'admin_order_update_status', methods: ['POST'])]
    public function updateStatus(
        Request $request,
        Order $order,
        EntityManagerInterface $entityManager
    ): Response {
        $newStatus = $request->request->get('status');

        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($newStatus, $validStatuses)) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
        }

        $oldStatus = $order->getStatus();
        $order->setStatus($newStatus);
        $order->setUpdatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', "Statut de la commande #{$order->getOrderNumber()} mis à jour : {$oldStatus} → {$newStatus}");
        
        return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/invoice', name: 'admin_order_invoice')]
    public function invoice(Order $order): Response
    {
        return $this->render('admin/orders/invoice.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/delivery-note', name: 'admin_order_delivery_note')]
    public function deliveryNote(Order $order): Response
    {
        return $this->render('admin/orders/delivery_note.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_order_delete', methods: ['POST'])]
    public function delete(
        Order $order,
        EntityManagerInterface $entityManager
    ): Response {
        $orderNumber = $order->getOrderNumber();
        
        $entityManager->remove($order);
        $entityManager->flush();

        $this->addFlash('success', "Commande #{$orderNumber} supprimée avec succès !");
        return $this->redirectToRoute('admin_orders');
    }
} 