<?php

namespace App\Controller\Design;

use App\Entity\User;
use App\Repository\DesignerTeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DesignerHuntController extends AbstractController
{
    #[Route('/designer/hunt', name: 'app_designer_hunt')]
    public function index(): Response
    {
        return $this->render('designer/hunt/index.html.twig', [
            'controller_name' => 'DesignerHuntController',
        ]);
    }

    #[Route('/designer/hunt/details/{id}', name: 'app_designer_hunt_details')]
    public function details(int $id): Response
    {
        return $this->render('designer/hunt/details.html.twig');
    }

    #[Route('/designer/hunt/create', name: 'app_designer_hunt_create')]
    public function create(Request $request, DesignerTeamRepository $designerTeamRepository, Security $security): Response
    {
        $designerTeamId = $request->query->get('designerTeamId');

        /**
         * @var User $currentUser
         */
        $currentUser = $security->getUser();

        $designerTeams = $designerTeamRepository->findByMemberOrOwner($currentUser);

        return $this->render('designer/hunt/create.html.twig', [
            'designerTeamId' => $designerTeamId,
            'designerTeams' => $designerTeams,
        ]);
    }
}
