<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Service\PictureService;
use App\Trait\OwnershipCheckTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class DeleteUserPictureController extends AbstractController
{
    use OwnershipCheckTrait;

    public function __invoke(User $data, EntityManagerInterface $em, PictureService $pictureService): Response
    {
        $this->checkOwnership($data);

        $pictureService->deleteEntityImage($data, 'getProfilePicture', 'setProfilePicture', $em);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
