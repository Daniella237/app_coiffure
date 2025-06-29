<?php

namespace App\Controller\Admin;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/settings')]
#[IsGranted('ROLE_ADMIN')]
class SettingsController extends AbstractController
{
    #[Route('/', name: 'admin_settings')]
    public function index(
        Request $request,
        SettingRepository $settingRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST')) {
            $this->handleSettingsUpdate($request, $settingRepository, $entityManager);
            $this->addFlash('success', 'Paramètres mis à jour avec succès !');
            return $this->redirectToRoute('admin_settings');
        }

        $settings = $this->getAllSettings($settingRepository);

        return $this->render('admin/settings/index.html.twig', [
            'settings' => $settings,
        ]);
    }

    private function handleSettingsUpdate(
        Request $request,
        SettingRepository $settingRepository,
        EntityManagerInterface $entityManager
    ): void {
        $settingsToUpdate = [
            // Horaires d'ouverture
            'opening_hours_monday' => $request->request->get('opening_hours_monday'),
            'opening_hours_tuesday' => $request->request->get('opening_hours_tuesday'),
            'opening_hours_wednesday' => $request->request->get('opening_hours_wednesday'),
            'opening_hours_thursday' => $request->request->get('opening_hours_thursday'),
            'opening_hours_friday' => $request->request->get('opening_hours_friday'),
            'opening_hours_saturday' => $request->request->get('opening_hours_saturday'),
            'opening_hours_sunday' => $request->request->get('opening_hours_sunday'),
            
            // Mode promotion
            'promo_mode_enabled' => $request->request->get('promo_mode_enabled') ? '1' : '0',
            'promo_discount_percentage' => $request->request->get('promo_discount_percentage'),
            'promo_message' => $request->request->get('promo_message'),
            
            // Emails automatiques
            'email_confirmation_enabled' => $request->request->get('email_confirmation_enabled') ? '1' : '0',
            'email_reminder_enabled' => $request->request->get('email_reminder_enabled') ? '1' : '0',
            'email_reminder_hours' => $request->request->get('email_reminder_hours'),
            'email_newsletter_enabled' => $request->request->get('email_newsletter_enabled') ? '1' : '0',
            
            // Informations générales
            'salon_name' => $request->request->get('salon_name'),
            'salon_address' => $request->request->get('salon_address'),
            'salon_phone' => $request->request->get('salon_phone'),
            'salon_email' => $request->request->get('salon_email'),
        ];

        foreach ($settingsToUpdate as $key => $value) {
            $setting = $settingRepository->findOneBy(['key' => $key]);
            
            if (!$setting) {
                $setting = new Setting();
                $setting->setKey($key);
                $entityManager->persist($setting);
            }
            
            $setting->setValue($value);
        }

        $entityManager->flush();
    }

    private function getAllSettings(SettingRepository $settingRepository): array
    {
        $settings = [];
        $allSettings = $settingRepository->findAll();
        
        foreach ($allSettings as $setting) {
            $settings[$setting->getKey()] = $setting->getValue();
        }

        // Valeurs par défaut si non définies
        $defaults = [
            'opening_hours_monday' => '09:00-18:00',
            'opening_hours_tuesday' => '09:00-18:00',
            'opening_hours_wednesday' => '09:00-18:00',
            'opening_hours_thursday' => '09:00-18:00',
            'opening_hours_friday' => '09:00-18:00',
            'opening_hours_saturday' => '09:00-17:00',
            'opening_hours_sunday' => 'Fermé',
            'promo_mode_enabled' => '0',
            'promo_discount_percentage' => '10',
            'promo_message' => 'Promotion en cours !',
            'email_confirmation_enabled' => '1',
            'email_reminder_enabled' => '1',
            'email_reminder_hours' => '24',
            'email_newsletter_enabled' => '1',
            'salon_name' => 'Salon de Beauté Glowly',
            'salon_address' => '123 Rue de la Beauté, 75001 Paris',
            'salon_phone' => '+33 1 23 45 67 89',
            'salon_email' => 'contact@glowly.fr',
        ];

        return array_merge($defaults, $settings);
    }
} 