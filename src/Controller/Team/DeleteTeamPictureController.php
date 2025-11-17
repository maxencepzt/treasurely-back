<?php

namespace App\Controller\Team;

use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class DeleteTeamPictureController extends AbstractController
{
    public function __invoke(Team $data, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles) || $data->getOwner() === $user) {
            $image = $data->getImage();
            $em->remove($image);
            $data->setImage(null);
            $em->persist($data);
            $em->flush();

            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        return new Response(null, Response::HTTP_FORBIDDEN);
    }
}
