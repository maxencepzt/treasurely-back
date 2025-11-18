<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Service\PictureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class GetUserPictureController extends AbstractController
{
    public function __invoke(User $user, PictureService $pictureService): Response
    {
        return $pictureService->getImageResponse(
            $user->getProfilePicture(),
            'public/images/default_picture.png'
        );
    }
}
