<?php

namespace App\Controller\Team;

use App\Entity\Team;
use App\Service\PictureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class GetTeamPictureController extends AbstractController
{
    public function __invoke(Team $team, PictureService $pictureService): Response
    {
        return $pictureService->getImageResponse(
            $team->getImage(),
            'public/images/default_picture.png'
        );
    }
}
