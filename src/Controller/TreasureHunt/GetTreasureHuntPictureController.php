<?php

namespace App\Controller\TreasureHunt;

use App\Entity\TreasureHunt;
use App\Service\PictureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class GetTreasureHuntPictureController extends AbstractController
{
    public function __invoke(TreasureHunt $treasureHunt, PictureService $pictureService): Response
    {
        return $pictureService->getImageResponse(
            $treasureHunt->getImage(),
            'public/images/default_picture.png'
        );
    }
}
