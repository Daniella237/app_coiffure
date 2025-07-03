<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();
        
        // Vérifier s'il y a un paramètre redirect dans la session (passé depuis le formulaire)
        $redirectPath = $request->getSession()->get('redirect_after_login');
        
        if ($redirectPath) {
            // Nettoyer la session
            $request->getSession()->remove('redirect_after_login');
            
            // Rediriger vers l'URL spécifiée
            if (str_starts_with($redirectPath, 'http')) {
                return new RedirectResponse($redirectPath);
            } else {
                // S'assurer que le chemin commence par /
                if (!str_starts_with($redirectPath, '/')) {
                    $redirectPath = '/' . $redirectPath;
                }
                return new RedirectResponse($redirectPath);
            }
        }

        // Redirection par défaut basée sur le rôle
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        } elseif (in_array('ROLE_EMPLOYEE', $user->getRoles())) {
            return new RedirectResponse($this->urlGenerator->generate('employee_dashboard'));
        } else {
            return new RedirectResponse($this->urlGenerator->generate('client_dashboard'));
        }
    }
} 