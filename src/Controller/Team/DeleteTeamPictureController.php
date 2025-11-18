<?php

namespace App\Controller\Team;

use App\Entity\Team;
use App\Service\PictureService;
use App\Trait\OwnershipCheckTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class DeleteTeamPictureController extends AbstractController
{
    use OwnershipCheckTrait;

    public function __invoke(Team $data, EntityManagerInterface $em, PictureService $pictureService): Response
    {
        $this->checkOwnership($data);

        $pictureService->deleteEntityImage($data, 'getImage', 'setImage', $em);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
