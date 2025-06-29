<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use App\Entity\ServiceCategory;
use App\Repository\ServiceRepository;
use App\Repository\ServiceCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/services')]
#[IsGranted('ROLE_ADMIN')]
class ServiceController extends AbstractController
{
    #[Route('/', name: 'admin_services')]
    public function index(ServiceRepository $serviceRepository, ServiceCategoryRepository $categoryRepository): Response
    {
        // Statistiques
        $totalServices = $serviceRepository->count([]);
        $activeServices = $serviceRepository->count(['isActive' => true]);
        $totalCategories = $categoryRepository->count(['isActive' => true]);
        
        
        $avgPrice = $serviceRepository->createQueryBuilder('s')
            ->select('AVG(s.price)')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Récupérer toutes les catégories avec leurs services
        $serviceCategories = $categoryRepository->createQueryBuilder('c')
            ->leftJoin('c.services', 's')
            ->addSelect('s')
            ->where('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/services/index.html.twig', [
            'service_categories' => $serviceCategories,
            'total_services' => $totalServices,
            'active_services' => $activeServices,
            'total_categories' => $totalCategories,
            'average_price' => $avgPrice,
        ]);
    }

    #[Route('/new', name: 'admin_service_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ServiceCategoryRepository $categoryRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $price = $request->request->get('price');
            $durationMinutes = $request->request->get('duration_minutes');
            $categoryId = $request->request->get('category');
            $isActive = $request->request->get('is_active') ? true : false;

            $category = $categoryRepository->find($categoryId);

            if ($name && $price && $durationMinutes && $category) {
                $service = new Service();
                $service->setName($name)
                        ->setDescription($description)
                        ->setPrice((float) $price)
                        ->setDurationMinutes((int) $durationMinutes)
                        ->setCategory($category)
                        ->setIsActive($isActive);

                $entityManager->persist($service);
                $entityManager->flush();

                $this->addFlash('success', 'Service créé avec succès !');
                return $this->redirectToRoute('admin_services');
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            }
        }

        $categories = $categoryRepository->findBy(['isActive' => true], ['sortOrder' => 'ASC']);

        return $this->render('admin/services/new.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_service_edit')]
    public function edit(
        Request $request,
        Service $service,
        EntityManagerInterface $entityManager,
        ServiceCategoryRepository $categoryRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $price = $request->request->get('price');
            $durationMinutes = $request->request->get('duration_minutes');
            $categoryId = $request->request->get('category');
            $isActive = $request->request->get('is_active') ? true : false;

            $category = $categoryRepository->find($categoryId);

            if ($name && $price && $durationMinutes && $category) {
                $service->setName($name)
                        ->setDescription($description)
                        ->setPrice((float) $price)
                        ->setDurationMinutes((int) $durationMinutes)
                        ->setCategory($category)
                        ->setIsActive($isActive);

                $entityManager->flush();

                $this->addFlash('success', 'Service modifié avec succès !');
                return $this->redirectToRoute('admin_services');
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            }
        }

        $categories = $categoryRepository->findBy(['isActive' => true], ['sortOrder' => 'ASC']);

        return $this->render('admin/services/edit.html.twig', [
            'service' => $service,
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_service_delete', methods: ['POST'])]
    public function delete(
        Service $service,
        EntityManagerInterface $entityManager
    ): Response {
        $entityManager->remove($service);
        $entityManager->flush();

        $this->addFlash('success', 'Service supprimé avec succès !');
        return $this->redirectToRoute('admin_services');
    }

    #[Route('/{id}/toggle', name: 'admin_service_toggle', methods: ['POST'])]
    public function toggle(
        Service $service,
        EntityManagerInterface $entityManager
    ): Response {
        $service->setIsActive(!$service->isActive());
        $entityManager->flush();

        $status = $service->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Service {$status} avec succès !");
        
        return $this->redirectToRoute('admin_services');
    }
} 