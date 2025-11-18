<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Service\ImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UploadUserPictureController extends AbstractController
{
    public function __invoke(User $data, EntityManagerInterface $em, Request $request, ImageUploadService $imageUploadService): Response
    {
        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('image');

        /**
         * @var User|null $userLogged
         */
        $userLogged = $this->getUser();
        if (null === $userLogged || $userLogged->getId() !== $data->getId()) {
            return $this->json(['error' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }

        if (!$uploadedFile) {
            return $this->json(['error' => 'Aucun fichier téléchargé ou clé invalide.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $picture = $imageUploadService->uploadImage($uploadedFile);

            $data->setProfilePicture($picture);
            $em->persist($data);
            $em->flush();

            return $this->json([
                'message' => 'Image mise à jour avec succès',
                'id' => $picture->getId(),
            ], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors du traitement.',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
