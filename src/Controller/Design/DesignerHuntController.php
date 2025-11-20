<?php

namespace App\Controller\Design;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function create(): Response
    {
        return $this->render('designer/hunt/create.html.twig');
    }
}
