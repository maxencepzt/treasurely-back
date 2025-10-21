<?php

namespace App\Controller;

use App\Entity\Picture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PictureController extends AbstractController
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    #[Route('/api/pictures/upload', name: 'api_picture_blob_upload', methods: ['POST'])]
    public function upload(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('image');

        if (!$uploadedFile) {
            return $this->json(['error' => 'No file uploaded or invalid key.'], Response::HTTP_BAD_REQUEST);
        }

        if (UPLOAD_ERR_OK !== $uploadedFile->getError()) {
            return $this->json(['error' => $uploadedFile->getErrorMessage()], Response::HTTP_BAD_REQUEST);
        }

        if ($uploadedFile->getSize() > self::MAX_FILE_SIZE) {
            return $this->json([
                'error' => 'La taille du fichier dépasse la limite autorisée (5 Mo max).',
            ], Response::HTTP_BAD_REQUEST);
        }

        $mimeType = $uploadedFile->getMimeType();

        if (null === $mimeType || !\in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return $this->json([
                'error' => 'Le type de fichier n\'est pas autorisé. Seules les images (JPEG, PNG, GIF, WEBP, SVG) sont acceptées. Type reçu: '.$mimeType,
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $fileContent = \file_get_contents($uploadedFile->getPathname());

            if (false === $fileContent) {
                throw new \RuntimeException('Failed to read file content.');
            }

            $picture = new Picture();
            $picture->setImage($fileContent);

            $entityManager->persist($picture);
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Une erreur est survenue lors du traitement.', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'message' => 'Image téléversée avec succès',
            'id' => $picture->getId(),
        ], Response::HTTP_CREATED);
    }
}
