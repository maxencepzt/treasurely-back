<?php

namespace App\Controller\Design;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DesignerDashboardController extends AbstractController
{
    #[Route('/designer/dashboard', name: 'app_designer_dashboard')]
    public function index(): Response
    {
        return $this->render('designer/dashboard/index.html.twig', [
            'controller_name' => 'DesignerDashboardController',
        ]);
    }
}
