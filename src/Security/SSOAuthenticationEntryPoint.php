<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

readonly class SSOAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        #[Autowire('%env(FRONTEND_URL)%')]
        private string $frontendUrl,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        // Rediriger vers la page de login du frontend
        return new RedirectResponse($this->frontendUrl.'/login');
    }
}
