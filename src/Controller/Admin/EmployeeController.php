<?php

namespace App\Controller\Admin;

use App\Entity\Employee;
use App\Entity\User;
use App\Repository\EmployeeRepository;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/employees')]
#[IsGranted('ROLE_ADMIN')]
class EmployeeController extends AbstractController
{
    #[Route('/', name: 'admin_employees')]
    public function index(EmployeeRepository $employeeRepository): Response
    {
        $employees = $employeeRepository->findAll();

        return $this->render('admin/employees/index.html.twig', [
            'employees' => $employees,
        ]);
    }

    #[Route('/new', name: 'admin_employee_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if ($request->isMethod('POST')) {
            $firstName = $request->request->get('first_name');
            $lastName = $request->request->get('last_name');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $specialization = $request->request->get('specialization');
            $password = $request->request->get('password');
            $isAvailable = $request->request->get('is_available') ? true : false;

            if ($firstName && $lastName && $email && $password) {
                // Créer l'utilisateur
                $user = new User();
                $user->setFirstName($firstName)
                     ->setLastName($lastName)
                     ->setEmail($email)
                     ->setPhone($phone)
                     ->setIsActive(true)
                     ->setRoles(['ROLE_EMPLOYEE']);

                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);

                // Créer l'employé
                $employee = new Employee();
                $employee->setUser($user)
                        ->setSpecialties($specialization)
                        ->setIsAvailable($isAvailable);

                $entityManager->persist($user);
                $entityManager->persist($employee);
                $entityManager->flush();

                $this->addFlash('success', 'Employé créé avec succès !');
                return $this->redirectToRoute('admin_employees');
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            }
        }

        return $this->render('admin/employees/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_employee_edit')]
    public function edit(
        Request $request,
        Employee $employee,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if ($request->isMethod('POST')) {
            $firstName = $request->request->get('first_name');
            $lastName = $request->request->get('last_name');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $specialization = $request->request->get('specialization');
            $password = $request->request->get('password');
            $isAvailable = $request->request->get('is_available') ? true : false;
            $roles = $request->request->all('roles') ?: ['ROLE_EMPLOYEE'];

            if ($firstName && $lastName && $email) {
                $user = $employee->getUser();
                $user->setFirstName($firstName)
                     ->setLastName($lastName)
                     ->setEmail($email)
                     ->setPhone($phone)
                     ->setRoles($roles);

                if ($password) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);
                }

                $employee->setSpecialties($specialization)
                        ->setIsAvailable($isAvailable);

                $entityManager->flush();

                $this->addFlash('success', 'Employé modifié avec succès !');
                return $this->redirectToRoute('admin_employees');
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            }
        }

        return $this->render('admin/employees/edit.html.twig', [
            'employee' => $employee,
        ]);
    }

    #[Route('/{id}/schedule', name: 'admin_employee_schedule')]
    public function schedule(
        Employee $employee,
        AppointmentRepository $appointmentRepository
    ): Response {
        $appointments = $appointmentRepository->findBy(['employee' => $employee], ['appointmentDate' => 'ASC']);

        return $this->render('admin/employees/schedule.html.twig', [
            'employee' => $employee,
            'appointments' => $appointments,
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_employee_toggle', methods: ['POST'])]
    public function toggle(
        Employee $employee,
        EntityManagerInterface $entityManager
    ): Response {
        $employee->setIsAvailable(!$employee->isAvailable());
        $entityManager->flush();

        $status = $employee->isAvailable() ? 'activé' : 'désactivé';
        $user = $employee->getUser();
        $this->addFlash('success', "Employé {$user->getFirstName()} {$user->getLastName()} {$status} avec succès !");
        
        return $this->redirectToRoute('admin_employees');
    }

    #[Route('/{id}/delete', name: 'admin_employee_delete', methods: ['POST'])]
    public function delete(
        Employee $employee,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $employee->getUser();
        $employeeName = $user->getFirstName() . ' ' . $user->getLastName();
        
        $entityManager->remove($employee);
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', "Employé {$employeeName} supprimé avec succès !");
        return $this->redirectToRoute('admin_employees');
    }
} 
