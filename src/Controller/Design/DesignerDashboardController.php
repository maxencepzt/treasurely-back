<?php

namespace App\Controller\Design;

use App\Entity\User;
use App\Repository\RiddleRepository;
use App\Repository\TeamRepository;
use App\Repository\TreasureHuntRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DesignerDashboardController extends AbstractController
{
    #[Route('/designer/dashboard', name: 'app_designer_dashboard')]
    public function index(
        TeamRepository $teamRepository,
        TreasureHuntRepository $treasureHuntRepository,
        RiddleRepository $riddleRepository,
        Security $security,
    ): Response {
        /**
         * @var User $user
         */
        $user = $security->getUser();

        // Calculer les statistiques
        $nbTeams = $teamRepository->countByOwner($user);
        $nbMembers = $teamRepository->countMembersByOwner($user);
        $nbTreasureHunts = $treasureHuntRepository->countByTeamOwner($user);
        $nbRiddles = $riddleRepository->countByTeamOwner($user);

        return $this->render('designer/dashboard/index.html.twig', [
            'nb_teams' => $nbTeams,
            'nb_members' => $nbMembers,
            'nb_treasure_hunts' => $nbTreasureHunts,
            'nb_riddles' => $nbRiddles,
        ]);
    }
}
