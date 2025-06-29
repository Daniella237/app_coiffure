<?php

namespace App\Controller;

use App\Repository\ServiceRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ServiceRepository $serviceRepository,
        ProductRepository $productRepository
    ): Response {
        // Récupérer les catégories de services avec leurs services
        $serviceCategories = $serviceRepository->getServiceCategoriesWithServices();
        
        // Récupérer les produits par catégorie
        $productCategories = $productRepository->getProductCategoriesWithProducts();

        return $this->render('home/index.html.twig', [
            'serviceCategories' => $serviceCategories,
            'productCategories' => $productCategories,
        ]);
    }
} 