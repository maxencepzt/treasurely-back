<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SSOController extends AbstractController
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserRepository $userRepository,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.cache_dir%')]
        private readonly string $cacheDir,
    ) {
    }

    #[Route('/sso/login', name: 'sso_login', methods: ['POST'])]
    public function ssoLogin(Request $request): RedirectResponse|JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['token'])) {
            return new JsonResponse(['message' => 'Token not provided'], Response::HTTP_BAD_REQUEST);
        }

        $token = $data['token'];

        try {
            $payload = $this->jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($payload['username'])) {
            return new JsonResponse(['message' => 'Username not found in token'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['nickname' => $payload['username']]);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        // Créer la session Symfony
        $symfonyToken = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($symfonyToken);
        $request->getSession()->set('_security_main', serialize($symfonyToken));

        // Enregistrer l'id de session Symfony sur l'utilisateur pour pouvoir la détruire plus tard via API JWT
        $sessionId = $request->getSession()->getId();
        if ($sessionId) {
            $user->setSymfonySessionId($sessionId);
            $this->em->persist($user);
            $this->em->flush();
        }

        return $this->redirectToRoute('app_home_index');
    }

    #[Route('/api/sso/logout', name: 'api_sso_logout', methods: ['POST'])]
    public function ssoLogout(): JsonResponse
    {
        /**
         * @var User|null $user
         */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Not authenticated'], 401);
        }

        $sessionId = $user->getSymfonySessionId();
        if ($sessionId) {
            // Tenter de supprimer le fichier de session directement
            $sessionPaths = [
                $this->cacheDir.'/sessions/sess_'.$sessionId,
                sys_get_temp_dir().'/sess_'.$sessionId,
            ];

            foreach ($sessionPaths as $sessionFile) {
                if (file_exists($sessionFile)) {
                    try {
                        unlink($sessionFile);
                        break; // Session supprimée avec succès
                    } catch (\Exception $e) {
                        // Continue avec le prochain chemin ou ignore si échec
                    }
                }
            }

            // Nettoyer l'ID de session stocké
            $user->setSymfonySessionId(null);
            $this->em->persist($user);
            $this->em->flush();
        }

        return new JsonResponse(['message' => 'Logged out successfully'], 200);
    }

    #[Route('/sso/redirect/front', name: 'sso_redirect_front')]
    public function ssoRedirectFront(): Response
    {
        return new Response('<script>window.location.href = "http://localhost:5173/";</script>');
    }
}
