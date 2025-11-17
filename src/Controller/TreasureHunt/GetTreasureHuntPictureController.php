<?php

namespace App\Controller\TreasureHunt;

use App\Entity\TreasureHunt;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class GetTreasureHuntPictureController extends AbstractController
{
    public function __invoke(TreasureHunt $treasureHunt): Response
    {
        return new Response(stream_get_contents($treasureHunt->getImage()->getImage(), offset: 0), Response::HTTP_OK, ['Content-Type' => 'image/png']);
    }
}
