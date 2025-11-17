<?php

namespace App\Controller\Team;

use App\Entity\Team;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class GetTeamPictureController extends AbstractController
{
    public function __invoke(Team $team): Response
    {
        return new Response(stream_get_contents($team->getImage()->getImage(), offset: 0), Response::HTTP_OK, ['Content-Type' => 'image/png']);
    }
}
