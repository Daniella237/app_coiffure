<?php

namespace App\Controller;

use App\Repository\ServiceRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
} 