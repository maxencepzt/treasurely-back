<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Enum\JoinRequestStatus;
use App\Repository\TeamJoinRequestRepository;
use App\State\AcceptJoinRequestProcessor;
use App\State\MyTeamRequestsProvider;
use App\State\RefuseJoinRequestProcessor;
use App\State\TeamJoinRequestsProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * La demande d'un joueur à rejoindre une équipe de joueurs vue dans l'annuaire. Le
 * propriétaire l'accepte ou la refuse ; une seule demande par joueur et par équipe, et un
 * refus est définitif tant que le propriétaire n'efface pas la demande.
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/team_requests/{id}',
            openapi: new Operation(
                summary: 'A join request',
                description: 'Readable by the player who sent it and by the owner of the team.'
            ),
            normalizationContext: ['groups' => ['joinRequest:read']],
            security: "is_granted('ROLE_USER') and (object.getUser() == user or object.getTeam().getOwner() == user)",
        ),
        new GetCollection(
            uriTemplate: '/player_teams/{id}/requests',
            uriVariables: ['id' => new Link(fromClass: Team::class, toProperty: 'team')],
            provider: TeamJoinRequestsProvider::class,
            openapi: new Operation(
                summary: 'Pending join requests of a player team',
                description: 'The pending requests, oldest first, for the owner of the team only (403 otherwise).'
            ),
            normalizationContext: ['groups' => ['joinRequest:read']],
            security: "is_granted('ROLE_USER')",
        ),
        new GetCollection(
            uriTemplate: '/me/team_requests',
            provider: MyTeamRequestsProvider::class,
            openapi: new Operation(
                summary: 'My join requests',
                description: 'The requests the current user sent, whatever their status, newest first.'
            ),
            normalizationContext: ['groups' => ['joinRequest:read']],
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            uriTemplate: '/team_requests/{id}/accept',
            status: 200,
            deserialize: false,
            validate: false,
            processor: AcceptJoinRequestProcessor::class,
            openapi: new Operation(
                summary: 'Accept a join request',
                description: 'The owner of the team accepts a pending request: the player joins the team. 409 when the request is already decided.'
            ),
            normalizationContext: ['groups' => ['joinRequest:read']],
            security: "is_granted('ROLE_USER') and object.getTeam().getOwner() == user",
        ),
        new Post(
            uriTemplate: '/team_requests/{id}/refuse',
            status: 200,
            deserialize: false,
            validate: false,
            processor: RefuseJoinRequestProcessor::class,
            openapi: new Operation(
                summary: 'Refuse a join request',
                description: 'The owner of the team refuses a pending request. 409 when the request is already decided.'
            ),
            normalizationContext: ['groups' => ['joinRequest:read']],
            security: "is_granted('ROLE_USER') and object.getTeam().getOwner() == user",
        ),
        new Delete(
            uriTemplate: '/team_requests/{id}',
            openapi: new Operation(
                summary: 'Withdraw or remove a join request',
                description: 'The player withdraws their request, or the owner of the team removes it, which lets the player ask again.'
            ),
            security: "is_granted('ROLE_USER') and (object.getUser() == user or object.getTeam().getOwner() == user)",
        ),
    ]
)]
#[ORM\Entity(repositoryClass: TeamJoinRequestRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_TEAM_JOIN_REQUEST', fields: ['team', 'user'])]
class TeamJoinRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['joinRequest:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['joinRequest:read'])]
    private PlayerTeam $team;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['joinRequest:read'])]
    private User $user;

    #[ORM\Column(type: 'string', enumType: JoinRequestStatus::class)]
    #[Groups(['joinRequest:read'])]
    private JoinRequestStatus $status = JoinRequestStatus::PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['joinRequest:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['joinRequest:read'])]
    private ?\DateTimeImmutable $decidedAt = null;

    public function __construct(PlayerTeam $team, User $user)
    {
        $this->team = $team;
        $this->user = $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeam(): PlayerTeam
    {
        return $this->team;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getStatus(): JoinRequestStatus
    {
        return $this->status;
    }

    public function setStatus(JoinRequestStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isPending(): bool
    {
        return JoinRequestStatus::PENDING === $this->status;
    }

    public function accept(): static
    {
        $this->status = JoinRequestStatus::ACCEPTED;
        $this->decidedAt = new \DateTimeImmutable();

        return $this;
    }

    public function refuse(): static
    {
        $this->status = JoinRequestStatus::REFUSED;
        $this->decidedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDecidedAt(): ?\DateTimeImmutable
    {
        return $this->decidedAt;
    }

    public function __toString(): string
    {
        return sprintf('%s → %s', $this->user->getNickname(), $this->team->getName());
    }
}
