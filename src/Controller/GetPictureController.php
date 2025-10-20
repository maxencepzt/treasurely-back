<?php

namespace App\Controller;

use App\Entity\Picture;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class GetPictureController extends AbstractController
{
    public function __invoke(Picture $picture): Response
    {
        return new Response(stream_get_contents($picture->getImage(), offset: 0), Response::HTTP_OK, ['Content-Type' => 'image/png']);
    }
}
