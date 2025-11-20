<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Controller\Team\DeleteTeamPictureController;
use App\Controller\Team\GetTeamPictureController;
use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\InheritanceType;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            openapi: new Operation(
                summary: 'Team details',
                description: 'Retrieve detailed information about a specific team by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['team:read', 'team:id']],
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            uriTemplate: 'teams/new',
            openapi: new Operation(
                summary: 'Team creation',
                description: 'Create a new team by providing necessary details. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['team:read', 'team:id']],
            denormalizationContext: ['groups' => ['team:write', 'team:owner']],
            security: "is_granted('ROLE_USER')",
        ),
        new Patch(
            openapi: new Operation(
                summary: 'Update team',
                description: 'Update a specific team by their ID. Users can only update their own information. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['team:read', 'team:id']],
            denormalizationContext: ['groups' => ['team:write']],
            security: "is_granted('ROLE_USER') and object.getOwner() == user"
        ),
        new Delete(
            openapi: new Operation(
                summary: 'Delete team',
                description: 'Delete a specific team by their ID. Owner can only delete the team. Requires ROLE_USER permission.'
            ),
            security: "is_granted('ROLE_USER') and object.getOwner() == user"
        ),
        new Get(
            uriTemplate: '/teams/{id}/picture',
            formats: [
                'png' => 'image/png',
            ],
            controller: GetTeamPictureController::class,
            openapi: new Operation(
                summary: 'Retrieves the picture of the specified team by their ID.',
                description: 'Retrieves the PNG image corresponding to the picture of the team.',
            ),
            normalizationContext: ['groups' => 'team:picture'],
            security: "is_granted('ROLE_USER')"
        ),
        new Delete(
            uriTemplate: '/teams/{id}/picture',
            formats: [
                'png' => 'image/png',
            ],
            controller: DeleteTeamPictureController::class,
            openapi: new Operation(
                summary: 'Remove the picture from the team',
                description: 'Remove the PNG image corresponding to the picture of the team',
            ),
            denormalizationContext: ['groups' => ['team:picture']],
            security: "is_granted('ROLE_USER')",
        ),
        new Get(
            uriTemplate: 'teams/{id}/members',
            openapi: new Operation(
                summary: 'Teams members',
                description: 'Retrieve the nickname of each members from a specific team by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['team:members']],
            security: "is_granted('ROLE_USER')",
        ),
        new Get(
            uriTemplate: 'teams/{id}/treasure_hunts/designer',
            openapi: new Operation(
                summary: 'Teams treasure hunts',
                description: 'Retrieve detailed informations of the treasure hunts from a specific team by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['team:treasureHunts']],
            security: "is_granted('ROLE_USER')",
        ),
    ]
)]
#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discriminator', type: 'string')]
#[DiscriminatorMap([
    'playerTeam' => PlayerTeam::class,
    'designerTeam' => DesignerTeam::class,
])]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['team:id'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['team:read', 'team:write'])]
    private string $name;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['team:read', 'team:write'])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'ownedTeams')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['team:read', 'team:owner'])]
    private User $owner;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(['team:picture'])]
    private ?Picture $image = null;

    /**
     * @var Collection<int, User>&iterable<User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'teams', cascade: ['persist'])]
    #[Groups(['team:members'])]
    private Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getImage(): ?Picture
    {
        return $this->image;
    }

    public function setImage(?Picture $image): static
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getMembers(): Collection
    {
        // @phpstan-ignore-next-line isset.initializedProperty
        if (!isset($this->members)) {
            $this->members = new ArrayCollection();
        }

        return $this->members;
    }

    public function addMember(User $member): static
    {
        $members = $this->getMembers();
        if (!$members->contains($member)) {
            $members->add($member);
        }

        return $this;
    }

    public function removeMember(User $member): static
    {
        $this->getMembers()->removeElement($member);

        return $this;
    }

    /**
     * @return Collection<int, TreasureHunt>
     */
    #[Groups(['team:treasureHunts'])]
    public function getHunts(): Collection
    {
        return new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
