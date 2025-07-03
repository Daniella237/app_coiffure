<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\Employee;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\EmployeeRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/appointments')]
#[IsGranted('ROLE_ADMIN')]
class AppointmentController extends AbstractController
{
    #[Route('/', name: 'admin_appointments')]
    public function index(
        Request $request,
        AppointmentRepository $appointmentRepository,
        EmployeeRepository $employeeRepository,
        ServiceRepository $serviceRepository
    ): Response {
        
        $employeeId = $request->query->get('employee');
        $serviceId = $request->query->get('service');
        $date = $request->query->get('date');
        $status = $request->query->get('status');

        
        $qb = $appointmentRepository->createQueryBuilder('a')
            ->leftJoin('a.client', 'c')
            ->leftJoin('a.service', 's')
            ->leftJoin('a.employee', 'e')
            ->addSelect('c', 's', 'e')
            ->orderBy('a.appointmentDate', 'ASC');

        
        if (!$status || ($status !== 'cancelled' && $status !== 'all')) {
            $qb->andWhere('a.status != :cancelled')
               ->setParameter('cancelled', 'cancelled');
        }

        if ($employeeId) {
            $qb->andWhere('e.id = :employeeId')
               ->setParameter('employeeId', $employeeId);
        }

        if ($serviceId) {
            $qb->andWhere('s.id = :serviceId')
               ->setParameter('serviceId', $serviceId);
        }

        if ($date) {
            $dateObj = new \DateTime($date);
            $qb->andWhere('a.appointmentDate >= :dateStart')
               ->andWhere('a.appointmentDate < :dateEnd')
               ->setParameter('dateStart', $dateObj->format('Y-m-d 00:00:00'))
               ->setParameter('dateEnd', $dateObj->format('Y-m-d 23:59:59'));
        }

        if ($status && $status !== 'all') {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        $appointments = $qb->getQuery()->getResult();

        
        $employees = $employeeRepository->findAll();
        $services = $serviceRepository->createQueryBuilder('s')
            ->join('s.category', 'c')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/appointments/index.html.twig', [
            'appointments' => $appointments,
            'employees' => $employees,
            'services' => $services,
            'filters' => [
                'employee' => $employeeId,
                'service' => $serviceId,
                'date' => $date,
                'status' => $status,
            ],
        ]);
    }

    #[Route('/new', name: 'admin_appointment_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ServiceRepository $serviceRepository,
        EmployeeRepository $employeeRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('_csrf_token');
            if (!\Symfony\Component\Security\Csrf\CsrfTokenManagerInterface::class || !$this->isCsrfTokenValid('create_appointment', $submittedToken)) {
                $this->addFlash('error', 'Le token CSRF est invalide. Veuillez réessayer.');
                return $this->redirectToRoute('admin_appointment_new');
            }
            $clientId = $request->request->get('appointment[client]');
            $serviceId = $request->request->get('appointment[service]');
            $employeeId = $request->request->get('appointment[employee]');
            $date = $request->request->get('appointment[date]');
            $time = $request->request->get('appointment[time]');
            $status = $request->request->get('appointment[status]');
            $notes = $request->request->get('appointment[notes]');

            $client = null;
            $service = null;
            $employee = null;

            // Vérifier que les IDs ne sont pas vides avant de faire les requêtes
            if (!empty($clientId)) {
                $client = $userRepository->find($clientId);
            }
            if (!empty($serviceId)) {
                $service = $serviceRepository->find($serviceId);
            }
            if (!empty($employeeId)) {
                $employee = $employeeRepository->find($employeeId);
            }

            error_log('Client trouvé: ' . ($client ? $client->getId() : 'null'));
            error_log('Service trouvé: ' . ($service ? $service->getId() : 'null'));
            error_log('Employee trouvé: ' . ($employee ? $employee->getId() : 'null'));

            if ($client && $service && $employee && $date && $time) {
                // Combiner date et heure
                $appointmentDateTime = new \DateTime($date . ' ' . $time);
                $now = new \DateTime();
                
                // Vérifier si la date/heure est dans le passé
                if ($appointmentDateTime <= $now) {
                    $this->addFlash('error', 'Impossible de créer un rendez-vous avec une date/heure dans le passé.');
                } else {
                    $appointment = new Appointment();
                    $appointment->setClient($client)
                               ->setService($service)
                               ->setEmployee($employee)
                               ->setAppointmentDate($appointmentDateTime)
                               ->setStatus($status)
                               ->setPrice($service->getPrice())
                               ->setNotes($notes)
                               ->setCreatedAt(new \DateTime())
                               ->setUpdatedAt(new \DateTime());

                    $entityManager->persist($appointment);
                    $entityManager->flush();

                    $this->addFlash('success', 'Rendez-vous créé avec succès !');
                    return $this->redirectToRoute('admin_appointments');
                }
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            }
        }

        // Récupérer les clients avec le rôle ROLE_USER
        $connection = $entityManager->getConnection();
        $usersData = $connection->executeQuery(
            "SELECT id FROM users WHERE roles::text LIKE :role",
            ['role' => '%ROLE_USER%']
        )->fetchAllAssociative();

        $users = [];
        foreach ($usersData as $userData) {
            $user = $userRepository->find($userData['id']);
            if ($user) {
                $users[] = $user;
            }
        }

        $services = $serviceRepository->createQueryBuilder('s')
            ->join('s.category', 'c')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
        $employees = $employeeRepository->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')
            ->addSelect('u')
            ->getQuery()
            ->getResult();

        return $this->render('admin/appointments/new.html.twig', [
            'users' => $users,
            'services' => $services,
            'employees' => $employees,
        ]);
    }

    #[Route('/cancelled', name: 'admin_appointments_cancelled')]
    public function cancelled(
        Request $request,
        AppointmentRepository $appointmentRepository,
        EmployeeRepository $employeeRepository,
        ServiceRepository $serviceRepository
    ): Response {
        
        $employeeId = $request->query->get('employee');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        $page = max(1, (int) $request->query->get('page', 1));

        //  la requête pour les rendez-vous annulés
        $qb = $appointmentRepository->createQueryBuilder('a')
            ->leftJoin('a.client', 'c')
            ->leftJoin('a.service', 's')
            ->leftJoin('a.employee', 'e')
            ->addSelect('c', 's', 'e')
            ->where('a.status = :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->orderBy('a.appointmentDate', 'DESC');

        if ($employeeId) {
            $qb->andWhere('e.id = :employeeId')
               ->setParameter('employeeId', $employeeId);
        }

        if ($dateFrom) {
            $dateFromObj = new \DateTime($dateFrom);
            $qb->andWhere('a.appointmentDate >= :dateFrom')
               ->setParameter('dateFrom', $dateFromObj->format('Y-m-d 00:00:00'));
        }

        if ($dateTo) {
            $dateToObj = new \DateTime($dateTo);
            $qb->andWhere('a.appointmentDate <= :dateTo')
               ->setParameter('dateTo', $dateToObj->format('Y-m-d 23:59:59'));
        }

        $cancelledAppointments = $qb->getQuery()->getResult();

       
        $totalCancelled = count($cancelledAppointments);
        
        // Rendez-vous annulés ce mois
        $thisMonthStart = new \DateTime('first day of this month');
        $thisMonthEnd = new \DateTime('last day of this month');
        $thisMonthCancelled = $appointmentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.status = :cancelled')
            ->andWhere('a.appointmentDate >= :monthStart')
            ->andWhere('a.appointmentDate <= :monthEnd')
            ->setParameter('cancelled', 'cancelled')
            ->setParameter('monthStart', $thisMonthStart->format('Y-m-d 00:00:00'))
            ->setParameter('monthEnd', $thisMonthEnd->format('Y-m-d 23:59:59'))
            ->getQuery()
            ->getSingleScalarResult();

        
        $totalAppointments = $appointmentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        $cancellationRate = $totalAppointments > 0 ? ($totalCancelled / $totalAppointments) * 100 : 0;

        
        $employees = $employeeRepository->findAll();
        $services = $serviceRepository->createQueryBuilder('s')
            ->join('s.category', 'c')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/appointments/cancelled.html.twig', [
            'cancelled_appointments' => $cancelledAppointments,
            'this_month_cancelled' => $thisMonthCancelled,
            'cancellation_rate' => $cancellationRate,
            'employees' => $employees,
            'services' => $services,
            'page' => $page,
            'totalPages' => 1, // Simplifié pour l'instant
            'filters' => [
                'employee' => $employeeId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    #[Route('/{id}', name: 'admin_appointment_show')]
    public function show(Appointment $appointment): Response
    {
        return $this->render('admin/appointments/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_appointment_edit')]
    public function edit(
        Request $request,
        Appointment $appointment,
        EntityManagerInterface $entityManager,
        ServiceRepository $serviceRepository,
        EmployeeRepository $employeeRepository,
        UserRepository $userRepository
    ): Response {
        if ($request->isMethod('POST')) {
            // Log des données reçues
            error_log('POST reçu pour appointment ' . $appointment->getId());
            error_log('Client: ' . $request->request->get('appointment[client]'));
            error_log('Service: ' . $request->request->get('appointment[service]'));
            error_log('Employee: ' . $request->request->get('appointment[employee]'));
            error_log('Date: ' . $request->request->get('appointment[date]'));
            error_log('Time: ' . $request->request->get('appointment[time]'));
            error_log('Status: ' . $request->request->get('appointment[status]'));
            error_log('Notes: ' . $request->request->get('appointment[notes]'));

            $clientId = $request->request->get('appointment[client]');
            $serviceId = $request->request->get('appointment[service]');
            $employeeId = $request->request->get('appointment[employee]');
            $date = $request->request->get('appointment[date]');
            $time = $request->request->get('appointment[time]');
            $status = $request->request->get('appointment[status]');
            $notes = $request->request->get('appointment[notes]');

            $client = null;
            $service = null;
            $employee = null;

            // Vérifier que les IDs ne sont pas vides avant de faire les requêtes
            if (!empty($clientId)) {
                $client = $userRepository->find($clientId);
            }
            if (!empty($serviceId)) {
                $service = $serviceRepository->find($serviceId);
            }
            if (!empty($employeeId)) {
                $employee = $employeeRepository->find($employeeId);
            }

            error_log('Client trouvé: ' . ($client ? $client->getId() : 'null'));
            error_log('Service trouvé: ' . ($service ? $service->getId() : 'null'));
            error_log('Employee trouvé: ' . ($employee ? $employee->getId() : 'null'));

            if ($client && $service && $employee && $date && $time) {
                // Combiner date et heure
                $appointmentDateTime = new \DateTime($date . ' ' . $time);
                $now = new \DateTime();
                
                // Vérifier si la date/heure est dans le passé
                if ($appointmentDateTime <= $now) {
                    $this->addFlash('error', 'Impossible de modifier un rendez-vous avec une date/heure dans le passé.');
                    error_log('Erreur: date dans le passé');
                } else {
                    $appointment->setClient($client)
                               ->setService($service)
                               ->setEmployee($employee)
                               ->setAppointmentDate($appointmentDateTime)
                               ->setStatus($status)
                               ->setPrice($service->getPrice())
                               ->setNotes($notes);

                    $entityManager->flush();
                    error_log('Rendez-vous modifié avec succès');

                    $this->addFlash('success', 'Rendez-vous modifié avec succès !');
                    return $this->redirectToRoute('admin_appointments');
                }
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
                error_log('Erreur: champs manquants');
            }
        }

        // Récupérer les clients avec le rôle ROLE_USER
        $connection = $entityManager->getConnection();
        $usersData = $connection->executeQuery(
            "SELECT id FROM users WHERE roles::text LIKE :role",
            ['role' => '%ROLE_USER%']
        )->fetchAllAssociative();

        $users = [];
        foreach ($usersData as $userData) {
            $user = $userRepository->find($userData['id']);
            if ($user) {
                $users[] = $user;
            }
        }

        $services = $serviceRepository->findBy(['isActive' => true]);
        $employees = $employeeRepository->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')
            ->addSelect('u')
            ->getQuery()
            ->getResult();

        return $this->render('admin/appointments/edit.html.twig', [
            'appointment' => $appointment,
            'users' => $users,
            'services' => $services,
            'employees' => $employees,
        ]);
    }

    #[Route('/{id}/cancel', name: 'admin_appointment_cancel', methods: ['POST'])]
    public function cancel(
        Appointment $appointment,
        EntityManagerInterface $entityManager
    ): Response {
        $appointment->setStatus('cancelled');
        $entityManager->flush();

        $this->addFlash('success', 'Rendez-vous annulé avec succès !');
        return $this->redirectToRoute('admin_appointments');
    }

    #[Route('/{id}/reschedule', name: 'admin_appointment_reschedule', methods: ['POST'])]
    public function reschedule(
        Request $request,
        Appointment $appointment,
        EntityManagerInterface $entityManager
    ): Response {
        $newDate = $request->request->get('new_date');
        
        if ($newDate) {
            $appointment->setAppointmentDate(new \DateTime($newDate));
            $appointment->setStatus('confirmed');
            $entityManager->flush();

            $this->addFlash('success', 'Rendez-vous replanifié avec succès !');
        } else {
            $this->addFlash('error', 'Veuillez spécifier une nouvelle date.');
        }

        return $this->redirectToRoute('admin_appointments');
    }

    #[Route('/{id}/restore', name: 'admin_appointment_restore', methods: ['POST'])]
    public function restore(
        Appointment $appointment,
        EntityManagerInterface $entityManager
    ): Response {
        if ($appointment->getStatus() === 'cancelled') {
            $appointment->setStatus('confirmed');
            $entityManager->flush();

            $this->addFlash('success', 'Rendez-vous restauré avec succès !');
        } else {
            $this->addFlash('error', 'Ce rendez-vous n\'est pas annulé.');
        }

        return $this->redirectToRoute('admin_appointments_cancelled');
    }
} 