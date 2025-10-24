<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SSOLoginController extends AbstractController
{
    private JWTTokenManagerInterface $jwtManager;
    private UserRepository $userRepository;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage,
    ) {
        $this->jwtManager = $jwtManager;
        $this->userRepository = $userRepository;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/sso/login', name: 'auth_redirect', methods: ['POST'])]
    public function redirectWithToken(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['token'])) {
            return new Response('Token missing', Response::HTTP_BAD_REQUEST);
        }

        $token = $data['token'];

        try {
            $payload = $this->jwtManager->parse($token);
        } catch (\Exception $e) {
            return new Response('Invalid token', Response::HTTP_BAD_REQUEST);
        }

        if (!isset($payload['username'])) {
            return new Response('Invalid token payload', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['nickname' => $payload['username']]);
        if (!$user) {
            return new Response('User not found', Response::HTTP_NOT_FOUND);
        }

        // Créer la session Symfony
        $symfonyToken = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($symfonyToken);
        $request->getSession()->set('_security_main', serialize($symfonyToken));

        return $this->redirectToRoute('api_doc');
    }
}
