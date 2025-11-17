<?php

namespace App\Controller\User;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class GetUserPictureController extends AbstractController
{
    public function __invoke(User $user): Response
    {
        return new Response(stream_get_contents($user->getProfilePicture()->getImage(), offset: 0), Response::HTTP_OK, ['Content-Type' => 'image/png']);
    }
}
