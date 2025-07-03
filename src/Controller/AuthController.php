<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class AuthController extends AbstractController
{
    #[Route('/client/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        $this->redirectToDashboard();
        $error = $authenticationUtils->getLastAuthenticationError();
        
        $lastUsername = $authenticationUtils->getLastUsername();
        
        // Récupérer le paramètre redirect de l'URL
        $redirectPath = $request->query->get('redirect');
        
        // Si un paramètre redirect est présent, le stocker en session
        if ($redirectPath) {
            $request->getSession()->set('redirect_after_login', $redirectPath);
        }

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'redirect' => $redirectPath,
        ]);
    }

    #[Route('/client/logout', name: 'app_logout')]
    public function logout(): void
    {
        
    }

    #[Route('/client/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()) {
            return $this->redirectToDashboard();
        }
        
        // Récupérer le paramètre redirect de l'URL
        $redirectPath = $request->query->get('redirect');
        
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'email existe déjà
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée. Veuillez en choisir une autre ou vous connecter.');
                return $this->render('auth/register.html.twig', [
                    'registrationForm' => $form->createView(),
                    'error' => 'Cette adresse email est déjà utilisée.',
                    'redirect' => $redirectPath
                ]);
            }

            try {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
                
                // Si un paramètre redirect est présent, le passer à la page de connexion
                if ($redirectPath) {
                    return $this->redirectToRoute('app_login', ['redirect' => $redirectPath]);
                }
                
                return $this->redirectToRoute('app_login');
                
            } catch (\Exception $e) {
                // Gestion d'autres erreurs de base de données
                $this->addFlash('error', 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.');
                return $this->render('auth/register.html.twig', [
                    'registrationForm' => $form->createView(),
                    'error' => 'Erreur lors de la création du compte.',
                    'redirect' => $redirectPath
                ]);
            }
        }

        return $this->render('auth/register.html.twig', [
            'registrationForm' => $form->createView(),
            'error' => $form->getErrors(true)->count() > 0 ? 'Veuillez corriger les erreurs ci-dessous.' : null,
            'redirect' => $redirectPath
        ]);
    }

    #[Route('/client/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, TokenGeneratorInterface $tokenGenerator): Response
    {
        $this->redirectToDashboard();

        // Récupérer le paramètre redirect de l'URL
        $redirectPath = $request->query->get('redirect');

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                $token = $tokenGenerator->generateToken();
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));
                
                $entityManager->flush();

                // Construire l'URL de réinitialisation avec le paramètre redirect si présent
                $resetUrlParams = ['token' => $token];
                if ($redirectPath) {
                    $resetUrlParams['redirect'] = $redirectPath;
                }

                $email = (new TemplatedEmail())
                    ->from($_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@glowly.fr')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->htmlTemplate('emails/reset_password.html.twig')
                    ->context([
                        'user' => $user,
                        'token' => $token,
                        'resetUrl' => $this->generateUrl('app_reset_password', $resetUrlParams, \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL)
                    ]);

                $mailer->send($email);

                return $this->render('auth/forgot-password.html.twig', [
                    'success' => 'Un email de réinitialisation a été envoyé à votre adresse email.',
                    'redirect' => $redirectPath
                ]);
            }

            return $this->render('auth/forgot-password.html.twig', [
                'error' => 'Aucun compte trouvé avec cette adresse email.',
                'redirect' => $redirectPath
            ]);
        }

        return $this->render('auth/forgot-password.html.twig', [
            'error' => null,
            'success' => null,
            'redirect' => $redirectPath
        ]);
    }

    #[Route('/client/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(string $token, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $this->redirectToDashboard();
        
        // Récupérer le paramètre redirect de l'URL
        $redirectPath = $request->query->get('redirect');
        
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
            return $this->render('auth/reset-password.html.twig', [
                'error' => 'Le lien de réinitialisation est invalide ou a expiré.',
                'token' => $token,
                'redirect' => $redirectPath
            ]);
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirmPassword');

            if ($password !== $confirmPassword) {
                return $this->render('auth/reset-password.html.twig', [
                    'error' => 'Les mots de passe ne correspondent pas.',
                    'token' => $token,
                    'redirect' => $redirectPath
                ]);
            }

            if (strlen($password) < 8) {
                return $this->render('auth/reset-password.html.twig', [
                    'error' => 'Le mot de passe doit contenir au moins 8 caractères.',
                    'token' => $token,
                    'redirect' => $redirectPath
                ]);
            }

            $user->setPassword($userPasswordHasher->hashPassword($user, $password));
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès !');
            
            // Si un paramètre redirect est présent, le stocker en session pour après la connexion
            if ($redirectPath) {
                $request->getSession()->set('redirect_after_login', $redirectPath);
            }
            
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset-password.html.twig', [
            'token' => $token,
            'redirect' => $redirectPath
        ]);
    }

    public function redirectToDashboard(){
        if ($this->getUser()) {
            $user = $this->getUser();
            
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->redirectToRoute('admin_dashboard');
            } elseif (in_array('ROLE_EMPLOYEE', $user->getRoles())) {
                return $this->redirectToRoute('employee_dashboard');
            } else {
                return $this->redirectToRoute('client_dashboard');
            }
        }
    }
} 