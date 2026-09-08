<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Controller\Team\DeleteTeamPictureController;
use App\Controller\Team\GetTeamPictureController;
use App\Dto\JoinTeamInput;
use App\Dto\PlayerTeamInput;
use App\Repository\TeamRepository;
use App\State\CreatePlayerTeamProcessor;
use App\State\JoinTeamProcessor;
use App\State\LeaveTeamProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\InheritanceType;
use Gedmo\Mapping\Annotation as Gedmo;
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
        // L'annuaire des équipes de joueurs (App\Doctrine\PlayerTeamsExtension), cherchable par nom
        new GetCollection(
            uriTemplate: 'player_teams',
            name: 'player_teams',
            paginationItemsPerPage: 20,
            openapi: new Operation(
                summary: 'Player teams directory',
                description: 'List the player teams, twenty per page, searchable by name (`?name=`). Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['team:read', 'team:id']],
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            uriTemplate: 'player_teams',
            input: PlayerTeamInput::class,
            processor: CreatePlayerTeamProcessor::class,
            openapi: new Operation(
                summary: 'Create a player team',
                description: 'Create a player team from a name and a description. The current user becomes its owner and first member; the join code is generated. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['team:read', 'team:id']],
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            uriTemplate: 'player_teams/join',
            status: 200,
            input: JoinTeamInput::class,
            processor: JoinTeamProcessor::class,
            openapi: new Operation(
                summary: 'Join a player team by its code',
                description: 'Join the player team whose join code is given: 404 for an unknown code, 409 when already a member. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['team:read', 'team:id']],
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            uriTemplate: 'teams/{id}/leave',
            status: 204,
            output: false,
            deserialize: false,
            validate: false,
            processor: LeaveTeamProcessor::class,
            openapi: new Operation(
                summary: 'Leave a team',
                description: 'Leave the team: 409 for its owner, who can only delete it, and for someone who is not a member. Requires ROLE_USER permission.'
            ),
            security: "is_granted('ROLE_USER')",
        ),
        // Le code de jointure vaut invitation : seuls les membres le lisent
        new Get(
            uriTemplate: 'player_teams/{id}/code',
            openapi: new Operation(
                summary: 'Join code of a player team',
                description: 'The join code of the team, for its members only. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['team:code']],
            security: "is_granted('ROLE_USER') and object.hasMember(user)",
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
            uriTemplate: 'teams/{id}/treasure_hunts',
            openapi: new Operation(
                summary: 'Teams treasure hunts',
                description: 'Retrieve detailed informations of the treasure hunts from a specific team by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['designerTeam:treasureHunts']],
            security: "is_granted('ROLE_USER')",
        ),
        new Get(
            uriTemplate: 'teams/{id}/participate_hunts',
            openapi: new Operation(
                summary: 'Player Teams treasure hunts participations',
                description: 'Retrieve detailed informations of the treasure hunts played from a specific team by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['playerTeam:treasureHunts']],
            security: "is_granted('ROLE_USER')",
        ),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'ipartial'])]
#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discriminator', type: 'string')]
#[DiscriminatorMap([
    'playerTeam' => PlayerTeam::class,
    'designerTeam' => DesignerTeam::class,
])]
abstract class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['team:id'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['team:read', 'team:write', 'user:teams', 'participateHunt:scoreboard'])]
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
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'teams', cascade: ['persist'])]
    #[Groups(['team:members'])]
    private Collection $members;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private \DateTimeImmutable $createdAt;

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

    public function hasMember(User $user): bool
    {
        return $this->getMembers()->contains($user);
    }

    /**
     * @return Collection<int, TreasureHunt>
     */
    #[Groups(['designerTeam:treasureHunts'])]
    public function getHunts(): Collection
    {
        return new ArrayCollection();
    }

    /**
     * @return Collection<int, ParticipateHunt>
     */
    #[Groups(['playerTeam:treasureHunts'])]
    public function getParticipateHunts(): Collection
    {
        return new ArrayCollection();
    }

    #[Groups(['team:code'])]
    public function getCode(): ?string
    {
        return null;
    }

    #[Groups(['team:read'])]
    public function getMemberCount(): int
    {
        return $this->getMembers()->count();
    }

    /**
     * Discriminant explicite du sous-type, `@var` valant "Team" pour les deux : une équipe
     * de joueurs rejoint des chasses, une équipe de concepteurs en écrit.
     */
    #[Groups(['team:read', 'user:teams'])]
    abstract public function getType(): string;

    public function __toString(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
