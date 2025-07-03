<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Repository\ProductRepository;
use App\Repository\ProductCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/products')]
#[IsGranted('ROLE_ADMIN')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'admin_products')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Grouper les produits par catégorie
        $groupedProducts = [];
        foreach ($products as $product) {
            $categoryName = $product->getCategory()->getName();
            if (!isset($groupedProducts[$categoryName])) {
                $groupedProducts[$categoryName] = [
                    'category' => $product->getCategory(),
                    'products' => []
                ];
            }
            $groupedProducts[$categoryName]['products'][] = $product;
        }

        return $this->render('admin/products/index.html.twig', [
            'groupedProducts' => $groupedProducts,
        ]);
    }

    #[Route('/new', name: 'admin_product_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductCategoryRepository $categoryRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $price = $request->request->get('price');
            $stockQuantity = $request->request->get('stock_quantity');
            $sku = $request->request->get('sku');
            $categoryId = $request->request->get('category');
            $isActive = $request->request->get('is_active') ? true : false;

            $category = $categoryRepository->find($categoryId);

            if ($name && $price && $stockQuantity && $category) {
                $product = new Product();
                $product->setName($name)
                        ->setDescription($description)
                        ->setPrice((float) $price)
                        ->setStockQuantity((int) $stockQuantity)
                        ->setSku($sku)
                        ->setCategory($category)
                        ->setIsActive($isActive);

                $entityManager->persist($product);
                $entityManager->flush();

                $this->addFlash('success', 'Produit créé avec succès !');
                return $this->redirectToRoute('admin_products');
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            }
        }

        $categories = $categoryRepository->findBy(['isActive' => true], ['sortOrder' => 'ASC']);

        return $this->render('admin/products/new.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_product_edit')]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        ProductCategoryRepository $categoryRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $price = $request->request->get('price');
            $stockQuantity = $request->request->get('stock_quantity');
            $sku = $request->request->get('sku');
            $categoryId = $request->request->get('category');
            $isActive = $request->request->get('is_active') ? true : false;

            $category = $categoryRepository->find($categoryId);

            if ($name && $price && $stockQuantity && $category) {
                $product->setName($name)
                        ->setDescription($description)
                        ->setPrice((float) $price)
                        ->setStockQuantity((int) $stockQuantity)
                        ->setSku($sku)
                        ->setCategory($category)
                        ->setIsActive($isActive);

                $entityManager->flush();

                $this->addFlash('success', 'Produit modifié avec succès !');
                return $this->redirectToRoute('admin_products');
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            }
        }

        $categories = $categoryRepository->findBy(['isActive' => true], ['sortOrder' => 'ASC']);

        return $this->render('admin/products/edit.html.twig', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Produit supprimé avec succès !');
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException | \Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce produit car il est utilisé dans une ou plusieurs commandes.');
        }
        return $this->redirectToRoute('admin_products');
    }

    #[Route('/{id}/toggle', name: 'admin_product_toggle', methods: ['POST'])]
    public function toggle(
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {
        $product->setIsActive(!$product->isActive());
        $entityManager->flush();

        $status = $product->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Produit {$status} avec succès !");
        
        return $this->redirectToRoute('admin_products');
    }
} 