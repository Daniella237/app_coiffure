<?php

namespace App\Security;

use App\Entity\Appointment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AppointmentVoter extends Voter
{
    public const EDIT = 'APPOINTMENT_EDIT';

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::EDIT && $subject instanceof Appointment;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Appointment $appointment */
        $appointment = $subject;

        // On ne peut modifier que si la date/heure du rendez-vous est dans le futur
        return $appointment->getAppointmentDate() > new \DateTime();
    }
} 