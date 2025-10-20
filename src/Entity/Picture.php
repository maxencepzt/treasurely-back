<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Controller\GetPictureController;
use App\Repository\PictureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PictureRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: 'pictures/new',
            openapi: new Operation(
                summary: 'Picture creation',
                description: 'Create a new picture by providing necessary details. This endpoint is only accessible by admins.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'treasureHunts' => ['type' => 'array'],
                                ],
                            ],
                            'example' => [
                                'title' => 'type',
                                'treasureHunts' => [],
                            ],
                        ],
                    ])
                ),
            ),
            normalizationContext: ['groups' => ['picture:read', 'picture:id']],
            denormalizationContext: ['groups' => ['picture:write']],
            security: "is_granted('ROLE_ADMIN')",
        ),
        new Get(
            uriTemplate: '/pictures/{id}',
            formats: [
                'png' => 'image/png',
            ],
            controller: GetPictureController::class,
            openapi: new Operation(
                summary: 'Retrieves a picture',
                description: 'Retrieves the PNG image corresponding to a picture',
            ),
            security: "is_granted('ROLE_USER')",
        ),
        new Delete(
            openapi: new Operation(
                summary: 'Delete picture',
                description: 'Delete a specific picture by their ID. Only admins can delete.'
            ),
            normalizationContext: ['groups' => ['picture:read', 'picture:id']],
            denormalizationContext: ['groups' => ['picture:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
    ]
)]
class Picture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['picture:read', 'picture:id'])]
    private ?int $id = null;

    // TODO: Revoir le typage de $image, actuellement non typé correctement pour PHPStan.
    #[ORM\Column(type: Types::BLOB)]
    /** @phpstan-ignore-next-line Le type n'est pas compatible avec PHPStan (Types::BLOB) */
    private $image;

    public function getId(): ?int
    {
        return $this->id;
    }

    /** @phpstan-ignore-next-line Le type n'est pas compatible avec PHPStan (Types::BLOB) */
    public function getImage()
    {
        return $this->image;
    }

    /** @phpstan-ignore-next-line Le type n'est pas compatible avec PHPStan (Types::BLOB) */
    public function setImage($image): static
    {
        $this->image = $image;

        return $this;
    }
}
