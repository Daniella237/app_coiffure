<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function findByClient(int $clientId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.service', 's')
            ->join('a.employee', 'e')
            ->join('e.user', 'u')
            ->addSelect('s', 'e', 'u')
            ->andWhere('a.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('a.appointmentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingAppointments(\DateTime $from = null): array
    {
        $from = $from ?? new \DateTime();
        
        return $this->createQueryBuilder('a')
            ->join('a.client', 'c')
            ->join('a.service', 's')
            ->join('a.employee', 'e')
            ->join('e.user', 'u')
            ->addSelect('c', 's', 'e', 'u')
            ->andWhere('a.appointmentDate >= :from')
            ->andWhere('a.status != :cancelled')
            ->setParameter('from', $from)
            ->setParameter('cancelled', Appointment::STATUS_CANCELLED)
            ->orderBy('a.appointmentDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByEmployee(int $employeeId, \DateTime $from = null): array
    {
        $from = $from ?? new \DateTime();
        
        return $this->createQueryBuilder('a')
            ->join('a.client', 'c')
            ->join('a.service', 's')
            ->addSelect('c', 's')
            ->andWhere('a.employee = :employeeId')
            ->andWhere('a.appointmentDate >= :from')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('from', $from)
            ->orderBy('a.appointmentDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAppointmentsNeedingReminder(): array
    {
        $tomorrow = new \DateTime('+1 day');
        $tomorrowStart = clone $tomorrow;
        $tomorrowStart->setTime(0, 0, 0);
        $tomorrowEnd = clone $tomorrow;
        $tomorrowEnd->setTime(23, 59, 59);

        return $this->createQueryBuilder('a')
            ->join('a.client', 'c')
            ->addSelect('c')
            ->andWhere('a.appointmentDate BETWEEN :start AND :end')
            ->andWhere('a.reminderSentAt IS NULL')
            ->andWhere('a.status = :confirmed')
            ->setParameter('start', $tomorrowStart)
            ->setParameter('end', $tomorrowEnd)
            ->setParameter('confirmed', Appointment::STATUS_CONFIRMED)
            ->getQuery()
            ->getResult();
    }

    public function getWeeklyAppointments(int $weeks = 4): array
    {
        $connection = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT 
                DATE(a.appointment_date) as week_start, 
                COUNT(a.id) as count
            FROM appointments a
            WHERE a.appointment_date >= :start_date
            GROUP BY DATE(a.appointment_date)
            ORDER BY week_start ASC
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->bindValue('start_date', new \DateTime('-' . $weeks . ' weeks'), \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE);
        $result = $stmt->executeQuery();
        
        return $result->fetchAllAssociative();
    }

    public function getTotalSpentByUser($user): float
    {
        $qb = $this->createQueryBuilder('a')
            ->select('SUM(a.price) as total')
            ->where('a.client = :user')
            ->andWhere('a.status != :cancelled')
            ->setParameter('user', $user)
            ->setParameter('cancelled', Appointment::STATUS_CANCELLED);

        $result = $qb->getQuery()->getSingleResult();
        return $result['total'] ? (float) $result['total'] : 0.0;
    }

    public function getTotalSpentByUserCompleted($user): float
    {
        $qb = $this->createQueryBuilder('a')
            ->select('SUM(a.price) as total')
            ->where('a.client = :user')
            ->andWhere('a.status = :completed')
            ->setParameter('user', $user)
            ->setParameter('completed', Appointment::STATUS_COMPLETED);

        $result = $qb->getQuery()->getSingleResult();
        return $result['total'] ? (float) $result['total'] : 0.0;
    }

    public function getTotalRevenueFromCompleted(): float
    {
        $qb = $this->createQueryBuilder('a')
            ->select('SUM(a.price) as total')
            ->where('a.status = :completed')
            ->setParameter('completed', Appointment::STATUS_COMPLETED);

        $result = $qb->getQuery()->getSingleResult();
        return $result['total'] ? (float) $result['total'] : 0.0;
    }
} 