<?php

namespace App\Controller\Design;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DesignerTeamController extends AbstractController
{
    #[Route('/designer/team', name: 'app_designer_team')]
    public function index(): Response
    {
        return $this->render('designer/team/index.html.twig', [
            'controller_name' => 'DesignerTeamController',
        ]);
    }
}
