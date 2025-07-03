<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Appointment;
use App\Entity\User;
use App\Form\ProfileFormType;
use App\Repository\CartItemRepository;
use App\Repository\OrderRepository;
use App\Repository\AppointmentRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client')]
#[IsGranted('ROLE_USER')]
class ClientController extends AbstractController
{
    #[Route('/', name: 'client_dashboard')]
    public function dashboard(
        OrderRepository $orderRepository,
        AppointmentRepository $appointmentRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $recentOrders = $orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC'], 5);
        $upcomingAppointments = $appointmentRepository->findUpcomingByUser($user, 5);
        
        return $this->render('client/dashboard.html.twig', [
            'user' => $user,
            'recentOrders' => $recentOrders,
            'upcomingAppointments' => $upcomingAppointments,
        ]);
    }

    #[Route('/profile', name: 'client_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');
            return $this->redirectToRoute('client_profile');
        }

        return $this->render('client/profile.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }

    #[Route('/orders', name: 'client_orders')]
    public function orders(OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $orders = $orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('client/orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/orders/{id}', name: 'client_order_show')]
    public function orderShow(Order $order): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($order->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette commande.');
        }

        return $this->render('client/order_show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/appointments', name: 'client_appointments')]
    public function appointments(AppointmentRepository $appointmentRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $appointments = $appointmentRepository->findBy(['client' => $user], ['appointmentDate' => 'DESC']);

        return $this->render('client/appointments.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    #[Route('/appointments/{id}/cancel', name: 'client_appointment_cancel', methods: ['POST'])]
    public function cancelAppointment(Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($appointment->getClient() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce rendez-vous.');
        }

        if ($appointment->getStatus() === 'cancelled') {
            $this->addFlash('error', 'Ce rendez-vous est déjà annulé.');
            return $this->redirectToRoute('client_appointments');
        }

        $appointment->setStatus('cancelled');
        $entityManager->flush();

        $this->addFlash('success', 'Votre rendez-vous a été annulé avec succès.');
        return $this->redirectToRoute('client_appointments');
    }

    #[Route('/cart', name: 'client_cart')]
    public function cart(CartItemRepository $cartItemRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $cartItems = $cartItemRepository->findBy(['user' => $user]);

        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item->getProduct()->getPrice() * $item->getQuantity();
        }

        return $this->render('client/cart.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    #[Route('/cart/add/{productId}', name: 'client_cart_add', methods: ['POST'])]
    public function addToCart(
        int $productId,
        Request $request,
        ProductRepository $productRepository,
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $product = $productRepository->find($productId);
        
        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé.');
        }

        $quantity = (int) $request->request->get('quantity', 1);
        
        // Vérifier si le produit est déjà dans le panier
        $existingItem = $cartItemRepository->findOneBy(['user' => $user, 'product' => $product]);
        
        if ($existingItem) {
            $existingItem->setQuantity($existingItem->getQuantity() + $quantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setUser($user);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $entityManager->persist($cartItem);
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Produit ajouté au panier !');
        return $this->redirectToRoute('client_cart');
    }

    #[Route('/cart/update/{id}', name: 'client_cart_update', methods: ['POST'])]
    public function updateCart(
        CartItem $cartItem,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($cartItem->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet article.');
        }

        $quantity = (int) $request->request->get('quantity', 1);
        
        if ($quantity <= 0) {
            $entityManager->remove($cartItem);
        } else {
            $cartItem->setQuantity($quantity);
        }
        
        $entityManager->flush();
        
        return $this->redirectToRoute('client_cart');
    }

    #[Route('/cart/remove/{id}', name: 'client_cart_remove', methods: ['POST'])]
    public function removeFromCart(CartItem $cartItem, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($cartItem->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet article.');
        }

        $entityManager->remove($cartItem);
        $entityManager->flush();
        
        $this->addFlash('success', 'Produit retiré du panier.');
        return $this->redirectToRoute('client_cart');
    }

    #[Route('/cart/checkout', name: 'client_cart_checkout', methods: ['POST'])]
    public function checkout(
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $cartItems = $cartItemRepository->findBy(['user' => $user]);

        if (empty($cartItems)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('client_cart');
        }

        // Créer la commande
        $order = new Order();
        $order->setUser($user);
        $order->setStatus('pending');
        $order->setSubtotal('0');
        $order->setTaxAmount('0');
        $order->setShippingCost('0');
        $order->setTotalAmount('0');
        
        $total = 0;
        foreach ($cartItems as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($cartItem->getProduct());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setPrice($cartItem->getProduct()->getPrice());
            
            $total += $cartItem->getProduct()->getPrice() * $cartItem->getQuantity();
            
            $entityManager->persist($orderItem);
        }
        
        $order->setSubtotal((string) $total);
        $order->setTaxAmount('0');
        $order->setShippingCost('0');
        $order->setTotalAmount((string) $total);
        $entityManager->persist($order);
        
        // Vider le panier
        foreach ($cartItems as $cartItem) {
            $entityManager->remove($cartItem);
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre commande a été passée avec succès !');
        return $this->redirectToRoute('client_order_show', ['id' => $order->getId()]);
    }
} 