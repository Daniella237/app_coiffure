<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;

class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Personnaliser le message d'erreur selon le type d'exception
        $errorMessage = $this->getCustomErrorMessage($exception);
        
        // Créer une nouvelle exception avec le message personnalisé
        $customException = new BadCredentialsException($errorMessage);
        
        // Stocker l'exception personnalisée dans la session
        $request->getSession()->set('_security.last_error', $customException);
        
        return parent::onAuthenticationFailure($request, $customException);
    }
    
    private function getCustomErrorMessage(AuthenticationException $exception): string
    {
        if ($exception instanceof BadCredentialsException) {
            return 'Email ou mot de passe incorrect. Veuillez vérifier vos identifiants.';
        }
        
        if ($exception instanceof UserNotFoundException) {
            return 'Aucun compte trouvé avec cette adresse email. Veuillez créer un compte ou vérifier votre email.';
        }
        
        if ($exception instanceof DisabledException) {
            return 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.';
        }
        
        // Message générique pour les autres types d'erreurs
        return 'Une erreur est survenue lors de la connexion. Veuillez réessayer.';
    }
} 