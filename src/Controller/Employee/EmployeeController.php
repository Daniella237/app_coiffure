<?php

namespace App\Controller\Employee;

use App\Entity\Appointment;
use App\Entity\Employee;
use App\Repository\AppointmentRepository;
use App\Repository\OrderRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee')]
#[IsGranted('ROLE_EMPLOYEE')]
class EmployeeController extends AbstractController
{
    #[Route('/', name: 'employee_dashboard')]
    public function dashboard(
        AppointmentRepository $appointmentRepository,
        OrderRepository $orderRepository,
        ServiceRepository $serviceRepository
    ): Response {
        // Récupérer l'employé connecté
        $user = $this->getUser();
        $employee = $user->getEmployee();
        
        if (!$employee) {
            throw $this->createAccessDeniedException('Vous devez être un employé pour accéder à cette page.');
        }

        // Date d'aujourd'hui
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');

        // Rendez-vous du jour pour cet employé
        $todayAppointments = $appointmentRepository->createQueryBuilder('a')
            ->leftJoin('a.client', 'c')
            ->leftJoin('a.service', 's')
            ->addSelect('c', 's')
            ->where('a.appointmentDate >= :today')
            ->andWhere('a.appointmentDate < :tomorrow')
            ->andWhere('a.employee = :employee')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('employee', $employee)
            ->orderBy('a.appointmentDate', 'ASC')
            ->getQuery()
            ->getResult();

        // Prochains rendez-vous pour cet employé
        $upcomingAppointments = $appointmentRepository->createQueryBuilder('a')
            ->leftJoin('a.client', 'c')
            ->leftJoin('a.service', 's')
            ->addSelect('c', 's')
            ->where('a.appointmentDate > :now')
            ->andWhere('a.status IN (:statuses)')
            ->andWhere('a.employee = :employee')
            ->setParameter('now', new \DateTime())
            ->setParameter('statuses', ['pending', 'confirmed'])
            ->setParameter('employee', $employee)
            ->orderBy('a.appointmentDate', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Derniers rendez-vous de cet employé
        $recentAppointments = $appointmentRepository->createQueryBuilder('a')
            ->leftJoin('a.client', 'c')
            ->leftJoin('a.service', 's')
            ->addSelect('c', 's')
            ->where('a.employee = :employee')
            ->setParameter('employee', $employee)
            ->orderBy('a.appointmentDate', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Statistiques personnelles
        $stats = [
            'today_appointments_count' => count($todayAppointments),
            'upcoming_appointments_count' => count($upcomingAppointments),
            'total_appointments_this_month' => $appointmentRepository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.employee = :employee')
                ->andWhere('a.appointmentDate >= :monthStart')
                ->setParameter('employee', $employee)
                ->setParameter('monthStart', new \DateTime('first day of this month'))
                ->getQuery()
                ->getSingleScalarResult(),
            'total_revenue_this_month' => $appointmentRepository->createQueryBuilder('a')
                ->select('SUM(a.price)')
                ->where('a.employee = :employee')
                ->andWhere('a.status = :completed')
                ->andWhere('a.appointmentDate >= :monthStart')
                ->setParameter('employee', $employee)
                ->setParameter('completed', 'completed')
                ->setParameter('monthStart', new \DateTime('first day of this month'))
                ->getQuery()
                ->getSingleScalarResult() ?? 0,
        ];

        return $this->render('employee/dashboard.html.twig', [
            'employee' => $employee,
            'stats' => $stats,
            'todayAppointments' => $todayAppointments,
            'upcomingAppointments' => $upcomingAppointments,
            'recent_appointments' => $recentAppointments,
        ]);
    }

    #[Route('/appointments', name: 'employee_appointments')]
    public function appointments(
        Request $request,
        AppointmentRepository $appointmentRepository
    ): Response {
        $user = $this->getUser();
        $employee = $user->getEmployee();
        
        if (!$employee) {
            throw $this->createAccessDeniedException('Vous devez être un employé pour accéder à cette page.');
        }

        $date = $request->query->get('date');
        $status = $request->query->get('status');

        // Construire la requête pour les rendez-vous de cet employé
        $qb = $appointmentRepository->createQueryBuilder('a')
            ->leftJoin('a.client', 'c')
            ->leftJoin('a.service', 's')
            ->addSelect('c', 's')
            ->where('a.employee = :employee')
            ->setParameter('employee', $employee)
            ->orderBy('a.appointmentDate', 'ASC');

        // Filtrer par date si spécifiée
        if ($date) {
            $dateObj = new \DateTime($date);
            $qb->andWhere('a.appointmentDate >= :dateStart')
               ->andWhere('a.appointmentDate < :dateEnd')
               ->setParameter('dateStart', $dateObj->format('Y-m-d 00:00:00'))
               ->setParameter('dateEnd', $dateObj->format('Y-m-d 23:59:59'));
        }

        // Filtrer par statut si spécifié
        if ($status && $status !== 'all') {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        $appointments = $qb->getQuery()->getResult();

        return $this->render('employee/appointments.html.twig', [
            'appointments' => $appointments,
            'employee' => $employee,
            'filters' => [
                'date' => $date,
                'status' => $status,
            ],
        ]);
    }

    #[Route('/appointments/{id}', name: 'employee_appointment_show')]
    public function showAppointment(Appointment $appointment): Response
    {
        $user = $this->getUser();
        $employee = $user->getEmployee();
        
        // Vérifier que l'employé peut voir ce rendez-vous
        if ($appointment->getEmployee() !== $employee) {
            throw $this->createAccessDeniedException('Vous ne pouvez voir que vos propres rendez-vous.');
        }

        return $this->render('employee/appointment_show.html.twig', [
            'appointment' => $appointment,
            'employee' => $employee,
        ]);
    }

    #[Route('/appointments/{id}/edit', name: 'employee_appointment_edit')]
    public function editAppointment(
        Request $request,
        Appointment $appointment,
        ServiceRepository $serviceRepository
    ): Response {
        $user = $this->getUser();
        $employee = $user->getEmployee();
        
        // Vérifier que l'employé peut modifier ce rendez-vous
        if ($appointment->getEmployee() !== $employee) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres rendez-vous.');
        }

        if ($request->isMethod('POST')) {
            $date = $request->request->get('appointment[date]');
            $time = $request->request->get('appointment[time]');
            $status = $request->request->get('appointment[status]');
            $notes = $request->request->get('appointment[notes]');

            if ($date && $time) {
                // Combiner date et heure
                $appointmentDateTime = new \DateTime($date . ' ' . $time);
                $now = new \DateTime();
                
                // Vérifier si la date/heure est dans le passé
                if ($appointmentDateTime <= $now) {
                    $this->addFlash('error', 'Impossible de modifier un rendez-vous avec une date/heure dans le passé.');
                } else {
                    $appointment->setAppointmentDate($appointmentDateTime)
                               ->setStatus($status)
                               ->setNotes($notes);

                    $this->getDoctrine()->getManager()->flush();

                    $this->addFlash('success', 'Rendez-vous modifié avec succès !');
                    return $this->redirectToRoute('employee_appointments');
                }
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            }
        }

        $services = $serviceRepository->findBy(['isActive' => true]);

        return $this->render('employee/appointment_edit.html.twig', [
            'appointment' => $appointment,
            'employee' => $employee,
            'services' => $services,
        ]);
    }
} 