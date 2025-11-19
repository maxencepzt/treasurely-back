<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DesignerHuntController extends AbstractController
{
    #[Route('/designer/hunts', name: 'app_designer_hunt')]
    public function index(): Response
    {
        return $this->render('designer/hunt/index.html.twig', [
            'controller_name' => 'DesignerHuntController',
        ]);
    }
}
