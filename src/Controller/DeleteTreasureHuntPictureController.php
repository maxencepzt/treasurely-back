<?php

namespace App\Controller;

use App\Entity\TreasureHunt;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class DeleteTreasureHuntPictureController extends AbstractController
{
    public function __invoke(TreasureHunt $data, EntityManagerInterface $em): Response
    {
        $data->setImage(null);
        $em->persist($data);
        $em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
