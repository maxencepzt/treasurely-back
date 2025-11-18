<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class PictureService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {
    }

    /**
     * Retourne une réponse HTTP avec le contenu de l'image.
     * Si l'image est null et qu'une image par défaut est fournie, retourne l'image par défaut.
     */
    public function getImageResponse(mixed $image, ?string $defaultImagePath = null): Response
    {
        if (null === $image) {
            if (null === $defaultImagePath) {
                throw new NotFoundHttpException('Image not found');
            }

            return $this->getDefaultImageResponse($defaultImagePath);
        }

        $imageContent = stream_get_contents($image->getImage(), offset: 0);

        return new Response($imageContent, Response::HTTP_OK, ['Content-Type' => 'image/png']);
    }

    /**
     * Retourne une réponse HTTP avec l'image par défaut.
     */
    private function getDefaultImageResponse(string $relativePath): Response
    {
        $fullPath = $this->projectDir.'/'.$relativePath;

        if (!file_exists($fullPath)) {
            throw new NotFoundHttpException('Default image not found');
        }

        $imageContent = file_get_contents($fullPath);

        // Déterminer le type MIME basé sur l'extension du fichier
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return new Response($imageContent, Response::HTTP_OK, ['Content-Type' => $mimeType]);
    }

    /**
     * Supprime une image d'une entité.
     * Retourne true si la suppression a réussi, false sinon.
     */
    public function deleteEntityImage(
        object $entity,
        string $getterMethod,
        string $setterMethod,
        EntityManagerInterface $em,
    ): void {
        $image = $entity->$getterMethod();

        if (null !== $image) {
            $em->remove($image);
            $entity->$setterMethod(null);
            $em->persist($entity);
            $em->flush();
        }
    }
}
