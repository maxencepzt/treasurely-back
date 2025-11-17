<?php

namespace App\Service;

use App\Entity\Picture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploadService
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 Mo
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Valide et upload une image en base de données.
     *
     * @throws \InvalidArgumentException si le fichier est invalide
     * @throws \RuntimeException         si l'upload échoue
     */
    public function uploadImage(UploadedFile $uploadedFile): Picture
    {
        $this->validateFile($uploadedFile);

        $fileContent = file_get_contents($uploadedFile->getPathname());

        if (false === $fileContent) {
            throw new \RuntimeException('Impossible de lire le contenu du fichier.');
        }

        $picture = new Picture();
        $picture->setImage($fileContent);

        $this->entityManager->persist($picture);
        $this->entityManager->flush();

        return $picture;
    }

    /**
     * Valide le fichier uploadé.
     *
     * @throws \InvalidArgumentException si le fichier est invalide
     */
    private function validateFile(UploadedFile $uploadedFile): void
    {
        if (UPLOAD_ERR_OK !== $uploadedFile->getError()) {
            throw new \InvalidArgumentException($uploadedFile->getErrorMessage());
        }

        if ($uploadedFile->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(sprintf('La taille du fichier dépasse la limite autorisée (%d Mo max).', self::MAX_FILE_SIZE / (1024 * 1024)));
        }

        $mimeType = $uploadedFile->getMimeType();

        if (null === $mimeType || !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Le type de fichier n\'est pas autorisé. Seules les images (JPEG, PNG, GIF, WEBP) sont acceptées. Type reçu: %s', $mimeType ?? 'inconnu'));
        }
    }

    /**
     * Retourne la taille maximale autorisée en octets.
     */
    public function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }

    /**
     * Retourne les types MIME autorisés.
     *
     * @return string[]
     */
    public function getAllowedMimeTypes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }
}
