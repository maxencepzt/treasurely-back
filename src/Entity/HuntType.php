<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Repository\HuntTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: HuntTypeRepository::class)]
#[ApiResource(
    operations: [
        // Get collection of users (non-sensitive data only)
        new GetCollection(
            openapi: new Operation(
                summary: 'List of hunt types',
                description: 'Retrieve all hunt types. Each entry represents a "HuntType" resource. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['huntType:read']],
            security: "is_granted('ROLE_USER')",
        ),
        // Register a new user
        new Post(
            uriTemplate: 'create',
            openapi: new Operation(
                summary: 'HuntType creation',
                description: 'Create a new hunt type by providing necessary details. This endpoint is only accessible by admins.',
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
            normalizationContext: ['groups' => ['huntType:read', 'huntType:id']],
            denormalizationContext: ['groups' => ['huntType:write']],
            security: "is_granted('ROLE_ADMIN')",
        ),
        // Get a specific user by ID (detailed information, sensitive data excluded)
        new Get(
            openapi: new Operation(
                summary: 'HuntType details',
                description: 'Retrieve detailed information about a specific hunt type by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['huntType:read']],
            security: "is_granted('ROLE_USER')",
        ),
        // Update a specific user by ID (only the user themselves can update their information)
        new Patch(
            openapi: new Operation(
                summary: 'Update hunt type',
                description: 'Update a specific hunt type by their ID. Only admins can update these informations.',
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
            normalizationContext: ['groups' => ['huntType:read', 'huntType:id']],
            denormalizationContext: ['groups' => ['huntType:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        // Delete a specific user by ID (only the user themselves can delete their account)
        new Delete(
            openapi: new Operation(
                summary: 'Update hunt type',
                description: 'Delete a specific hunt type by their ID. Only admins can delete.'
            ),
            normalizationContext: ['groups' => ['huntType:read', 'huntType:id']],
            denormalizationContext: ['groups' => ['huntType:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
    ]
)]
class HuntType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['huntType:read', 'huntType:id'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['huntType:read', 'huntType:write'])]
    private string $title;

    /**
     * @var Collection<int, TreasureHunt>
     */
    #[ORM\ManyToMany(targetEntity: TreasureHunt::class, inversedBy: 'huntType', cascade: ['persist'])]
    #[Groups(['huntType:write'])]
    #[ORM\JoinColumn(nullable: true)]
    private Collection $treasureHunts;

    public function __construct()
    {
        $this->treasureHunts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection<int, TreasureHunt>
     */
    public function getTreasureHunts(): Collection
    {
        return $this->treasureHunts;
    }

    public function addTreasureHunt(TreasureHunt $treasureHunt): static
    {
        if (!$this->treasureHunts->contains($treasureHunt)) {
            $this->treasureHunts->add($treasureHunt);
            $treasureHunt->addHuntType($this);
        }

        return $this;
    }

    public function removeTreasureHunt(TreasureHunt $treasureHunt): static
    {
        if ($this->treasureHunts->removeElement($treasureHunt)) {
            $treasureHunt->removeHuntType($this);
        }

        return $this;
    }
}
