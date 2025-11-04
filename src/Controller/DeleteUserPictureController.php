<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class DeleteUserPictureController extends AbstractController
{
    public function __invoke(User $data, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles) || $data === $user) {
            $image = $data->getProfilePicture();
            $em->remove($image);
            $data->setProfilePicture(null);
            $em->persist($data);
            $em->flush();

            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        return new Response(null, Response::HTTP_FORBIDDEN);
    }
}
