<?php

namespace App\Controller\User;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class GetUserStatsController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/api/users/{id}/stats', name: 'api_user_stats', methods: ['GET'])]
    public function __invoke(int $id): JsonResponse
    {
        // Première requête: récupérer l'utilisateur par son ID
        $user = $this->userRepository->find($id);

        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found',
            ], 404);
        }

        // Calculer les statistiques en utilisant les méthodes du repository
        $totalHunt = $this->userRepository->getTotalHunt($user);
        $totalScore = $this->userRepository->getTotalScore($user);
        $totalRiddles = $this->userRepository->getTotalRiddles($user);
        $totalTime = $this->userRepository->getTotalTime($user);

        // Retourner les statistiques
        return new JsonResponse([
            'userId' => $user->getId(),
            'nickname' => $user->getNickname(),
            'stats' => [
                'totalHunt' => $totalHunt,
                'totalScore' => $totalScore,
                'totalRiddles' => $totalRiddles,
                'totalTime' => $totalTime,
            ],
            // Afficher aussi les valeurs stockées en base pour comparaison
            'storedStats' => [
                'totalHunt' => $user->getTotalHunt(),
                'totalScore' => $user->getTotalScore(),
                'totalRiddles' => $user->getTotalRiddles(),
                'totalTime' => $user->getTotalTime(),
            ],
        ]);
    }
}
