<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Controller\DeleteTeamPictureController;
use App\Controller\GetPictureController;
use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            openapi: new Operation(
                summary: 'Team details',
                description: 'Retrieve detailed information about a specific team by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['team:read']],
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            uriTemplate: 'teams/new',
            openapi: new Operation(
                summary: 'Team creation',
                description: 'Create a new team by providing necessary details. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['team:read', 'team:id']],
            denormalizationContext: ['groups' => ['team:write']],
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
            controller: GetPictureController::class,
            openapi: new Operation(
                summary: 'Retrieves the picture of the specified team by their ID.',
                description: 'Retrieves the PNG image corresponding to the picture of the team.',
            ),
            normalizationContext: ['groups' => 'team:picture'],
            security: "is_granted('ROLE_USER')"
        ),
        new Delete(
            uriTemplate: '/treasure_hunts/{id}/picture',
            formats: [
                'png' => 'image/png',
            ],
            controller: DeleteTeamPictureController::class,
            openapi: new Operation(
                summary: 'Remove the picture from the treasure hunt',
                description: 'Remove the PNG image corresponding to the picture of the treasure hunt',
            ),
            denormalizationContext: ['groups' => ['treasureHunt:picture']],
            security: "is_granted('ROLE_USER')",
        ),
    ]
)]
#[ORM\Entity(repositoryClass: TeamRepository::class)]
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
    #[Groups(['team:read'])]
    private User $owner;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(['team:picture'])]
    private ?Picture $image = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'teams', cascade: ['persist'])]
    private Collection $members;

    /**
     * @var Collection<int, TreasureHunt>
     */
    #[ORM\OneToMany(targetEntity: TreasureHunt::class, mappedBy: 'team', orphanRemoval: true)]
    private Collection $treasureHunts;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->treasureHunts = new ArrayCollection();
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
        return $this->members;
    }

    public function addMember(User $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
        }

        return $this;
    }

    public function removeMember(User $member): static
    {
        $this->members->removeElement($member);

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
            $treasureHunt->setTeam($this);
        }

        return $this;
    }

    public function removeTreasureHunt(TreasureHunt $treasureHunt): static
    {
        if ($this->treasureHunts->removeElement($treasureHunt)) {
            // set the owning side to null (unless already changed)
            if ($treasureHunt->getTeam() === $this) {
                $treasureHunt->setTeam(null);
            }
        }

        return $this;
    }
}
