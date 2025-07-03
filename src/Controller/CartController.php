<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\CartItemRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Stripe;
use Stripe\Checkout\Session;

#[Route('/client')]
class CartController extends AbstractController
{
    #[Route('/cart', name: 'client_cart')]
    public function index(CartItemRepository $cartItemRepository): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $cartItems = $cartItemRepository->findBy(['user' => $this->getUser()]);
        
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item->getTotalPrice();
        }

        return $this->render('client/cart.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    #[Route('/cart/update/{id}', name: 'cart_update', methods: ['POST'])]
    public function updateQuantity(
        CartItem $cartItem,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$this->getUser() || $cartItem->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $quantity = (int) $data['quantity'];

        if ($quantity <= 0) {
            return new JsonResponse(['error' => 'Quantité invalide'], 400);
        }

        if ($cartItem->getProduct()->getStockQuantity() < $quantity) {
            return new JsonResponse(['error' => 'Stock insuffisant'], 400);
        }

        $cartItem->setQuantity($quantity);
        $em->flush();

        return new JsonResponse([
            'success' => 'Quantité mise à jour',
            'newQuantity' => $quantity,
            'itemTotal' => $cartItem->getTotalPrice(),
            'productName' => $cartItem->getProduct()->getName()
        ]);
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['DELETE'])]
    public function removeItem(
        CartItem $cartItem,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$this->getUser() || $cartItem->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $productName = $cartItem->getProduct()->getName();
        $em->remove($cartItem);
        $em->flush();

        return new JsonResponse([
            'success' => 'Produit supprimé du panier',
            'productName' => $productName
        ]);
    }

    #[Route('/cart/clear', name: 'cart_clear', methods: ['DELETE'])]
    public function clearCart(
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $cartItems = $cartItemRepository->findBy(['user' => $this->getUser()]);
        
        foreach ($cartItems as $item) {
            $em->remove($item);
        }
        
        $em->flush();

        return new JsonResponse(['success' => 'Panier vidé']);
    }

    #[Route('/checkout', name: 'client_checkout')]
    public function checkout(
        CartItemRepository $cartItemRepository,
        Request $request
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $cartItems = $cartItemRepository->findBy(['user' => $this->getUser()]);
        
        if (empty($cartItems)) {
            $this->addFlash('error', 'Votre panier est vide');
            return $this->redirectToRoute('client_cart');
        }

        // Vérifier le stock avant de procéder au paiement
        foreach ($cartItems as $item) {
            if ($item->getProduct()->getStockQuantity() < $item->getQuantity()) {
                $this->addFlash('error', 'Stock insuffisant pour ' . $item->getProduct()->getName());
                return $this->redirectToRoute('client_cart');
            }
        }

        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item->getTotalPrice();
        }

        return $this->render('client/checkout.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    #[Route('/payment/create-session', name: 'payment_create_session', methods: ['POST'])]
    public function createPaymentSession(
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {
        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $cartItems = $cartItemRepository->findBy(['user' => $this->getUser()]);
        
        if (empty($cartItems)) {
            return new JsonResponse(['error' => 'Panier vide'], 400);
        }

        // Vérifier que les clés Stripe sont configurées
        $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? null;
        if (!$stripeSecretKey || $stripeSecretKey === 'sk_test_51234567890abcdef') {
            return new JsonResponse([
                'error' => 'Clés Stripe non configurées. Veuillez configurer vos vraies clés Stripe dans le fichier .env.local'
            ], 500);
        }

        // Configuration Stripe
        Stripe::setApiKey($stripeSecretKey);

        try {
            $lineItems = [];
            $total = 0;

            foreach ($cartItems as $item) {
                $product = $item->getProduct();
                
                // Vérifier le stock
                if ($product->getStockQuantity() < $item->getQuantity()) {
                    return new JsonResponse([
                        'error' => 'Stock insuffisant pour ' . $product->getName()
                    ], 400);
                }

                $itemTotal = $item->getTotalPrice();
                $total += $itemTotal;

                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $product->getName(),
                            'description' => $product->getDescription() ? substr($product->getDescription(), 0, 100) : '',
                        ],
                        'unit_amount' => intval($product->getPrice() * 100), // Montant en centimes
                    ],
                    'quantity' => $item->getQuantity(),
                ];
            }

            // Ajouter les frais de livraison si nécessaire
            if ($total < 50) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Frais de livraison',
                            'description' => 'Livraison standard',
                        ],
                        'unit_amount' => 490, // 4.90€ en centimes
                    ],
                    'quantity' => 1,
                ];
                $total += 4.90;
            }

            // Créer la session Stripe Checkout
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $request->getSchemeAndHttpHost() . '/client/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $request->getSchemeAndHttpHost() . '/client/cart',
                'metadata' => [
                    'user_id' => $this->getUser()->getId(),
                    'total_amount' => $total,
                ],
            ]);

            return new JsonResponse(['checkout_url' => $session->url]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new JsonResponse([
                'error' => 'Erreur Stripe: ' . $e->getMessage() . '. Vérifiez vos clés Stripe.'
            ], 500);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la création de la session de paiement: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/payment/success', name: 'payment_success')]
    public function paymentSuccess(
        Request $request,
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $em
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $sessionId = $request->query->get('session_id');
        
        if (!$sessionId) {
            $this->addFlash('error', 'Session de paiement invalide');
            return $this->redirectToRoute('client_cart');
        }

        try {
            // Configuration Stripe
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? 'sk_test_...');
            
            // Récupérer la session Stripe
            $session = Session::retrieve($sessionId);
            
            if ($session->payment_status === 'paid') {
                // Créer la commande
                $cartItems = $cartItemRepository->findBy(['user' => $this->getUser()]);
                
                if (!empty($cartItems)) {
                    $order = new Order();
                    $order->setClient($this->getUser());
                    $order->setOrderDate(new \DateTime());
                    $order->setStatus('paid');
                    $order->setStripeSessionId($sessionId);
                    
                    $totalAmount = 0;
                    
                    foreach ($cartItems as $cartItem) {
                        $product = $cartItem->getProduct();
                        
                        // Créer l'item de commande
                        $orderItem = new OrderItem();
                        $orderItem->setOrderEntity($order);
                        $orderItem->setProduct($product);
                        $orderItem->setQuantity($cartItem->getQuantity());
                        $orderItem->setPrice($product->getPrice());
                        
                        $totalAmount += $orderItem->getTotalPrice();
                        
                        // Décrémenter le stock
                        $newStock = $product->getStockQuantity() - $cartItem->getQuantity();
                        $product->setStockQuantity(max(0, $newStock));
                        
                        $em->persist($orderItem);
                        $em->remove($cartItem); // Supprimer du panier
                    }
                    
                    $order->setTotalAmount($totalAmount);
                    $em->persist($order);
                    $em->flush();
                    
                    $this->addFlash('success', 'Paiement réussi ! Votre commande a été enregistrée.');
                    return $this->render('client/payment_success.html.twig', [
                        'order' => $order,
                        'session' => $session,
                    ]);
                }
            }
            
            $this->addFlash('error', 'Le paiement n\'a pas été confirmé');
            return $this->redirectToRoute('client_cart');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la vérification du paiement: ' . $e->getMessage());
            return $this->redirectToRoute('client_cart');
        }
    }

    #[Route('/orders', name: 'client_orders')]
    public function orders(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $orders = $this->getUser()->getOrders();

        return $this->render('client/orders.html.twig', [
            'orders' => $orders,
        ]);
    }
} 