<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use App\Controller\TreasureHunt\DeleteTreasureHuntPictureController;
use App\Controller\TreasureHunt\GetTreasureHuntPictureController;
use App\Repository\TreasureHuntRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(
            openapi: new Operation(
                summary: 'Treasure hunt details',
                description: 'Retrieve detailed information about a specific treasure hunt by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['treasureHunt:read']],
            security: "is_granted('ROLE_USER')",
        ),
        new Get(
            uriTemplate: 'treasure_hunts/{id}/riddles',
            openapi: new Operation(
                summary: 'Treasure hunt riddles',
                description: 'Retrieve detailed informations of the riddles from a specific treasure hunt by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['treasureHunt:riddles']],
            security: "is_granted('ROLE_USER')",
        ),
        new Get(
            uriTemplate: '/treasure_hunts/{id}/picture',
            formats: [
                'png' => 'image/png',
            ],
            controller: GetTreasureHuntPictureController::class,
            openapi: new Operation(
                summary: 'Retrieves the picture from the treasure hunt',
                description: 'Retrieves the PNG image corresponding to the picture of the treasure hunt',
            ),
            normalizationContext: ['groups' => ['treasureHunt:picture']],
            security: "is_granted('ROLE_USER')",
        ),
        new Delete(
            uriTemplate: '/treasure_hunts/{id}/picture',
            formats: [
                'png' => 'image/png',
            ],
            controller: DeleteTreasureHuntPictureController::class,
            openapi: new Operation(
                summary: 'Remove the picture from the treasure hunt',
                description: 'Remove the PNG image corresponding to the picture of the treasure hunt',
            ),
            denormalizationContext: ['groups' => ['treasureHunt:picture']],
            security: "is_granted('ROLE_USER')",
        ),
    ]
)]
#[ORM\Entity(repositoryClass: TreasureHuntRepository::class)]
class TreasureHunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['treasureHunt:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Groups(['treasureHunt:read', 'team:treasureHunts'])]
    private string $title;

    #[ORM\Column(length: 3000, nullable: true)]
    #[Groups(['treasureHunt:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['treasureHunt:read'])]
    private bool $public = true;

    #[ORM\Column]
    #[Assert\Choice(
        choices: [1, 2, 3],
    )]
    #[Groups(['treasureHunt:read', 'team:treasureHunts'])]
    private int $difficulty;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['treasureHunt:read', 'team:treasureHunts'])]
    private int $riddleCount;

    /**
     * @var Collection<int, HuntType>
     */
    #[ORM\ManyToMany(targetEntity: HuntType::class, mappedBy: 'treasureHunts')]
    #[Groups(['treasureHunt:read'])]
    private Collection $huntType;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(['treasureHunt:picture'])]
    private ?Picture $image = null;

    #[ORM\ManyToOne(inversedBy: 'treasureHunts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['treasureHunt:read'])]
    private User $owner;

    /**
     * @var Collection<int, Riddle>
     */
    #[ORM\OneToMany(targetEntity: Riddle::class, mappedBy: 'hunt', orphanRemoval: true)]
    #[Groups(['treasureHunt:riddles'])]
    private Collection $riddles;

    #[ORM\ManyToOne(inversedBy: 'hunts')]
    #[ORM\JoinColumn(nullable: false)]
    private DesignerTeam $designerTeam;

    /**
     * @var Collection<int, ParticipateHunt>
     */
    #[ORM\OneToMany(targetEntity: ParticipateHunt::class, mappedBy: 'hunt')]
    private Collection $participateHunts;

    #[ORM\Column(length: 30)]
    #[Groups(['treasureHunt:read', 'team:treasureHunts'])]
    private string $location;

    public function __construct()
    {
        $this->huntType = new ArrayCollection();
        $this->riddles = new ArrayCollection();
        $this->participateHunts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function isPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getDifficulty(): ?int
    {
        return $this->difficulty;
    }

    public function setDifficulty(int $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getRiddleCount(): ?int
    {
        return $this->riddleCount;
    }

    public function setRiddleCount(int $riddleCount): static
    {
        $this->riddleCount = $riddleCount;

        return $this;
    }

    /**
     * @return Collection<int, HuntType>
     */
    public function getHuntType(): Collection
    {
        return $this->huntType;
    }

    public function addHuntType(HuntType $huntType): static
    {
        if (!$this->huntType->contains($huntType)) {
            $this->huntType->add($huntType);
            $huntType->addTreasureHunt($this);
        }

        return $this;
    }

    public function removeHuntType(HuntType $huntType): static
    {
        if ($this->huntType->removeElement($huntType)) {
            $huntType->removeTreasureHunt($this);
        }

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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, Riddle>
     */
    public function getRiddles(): Collection
    {
        return $this->riddles;
    }

    public function addRiddle(Riddle $riddle): static
    {
        if (!$this->riddles->contains($riddle)) {
            $this->riddles->add($riddle);
            $riddle->setHunt($this);
        }

        return $this;
    }

    public function removeRiddle(Riddle $riddle): static
    {
        if ($this->riddles->removeElement($riddle)) {
            // set the owning side to null (unless already changed)
            if ($riddle->getHunt() === $this) {
                $riddle->setHunt(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ParticipateHunt>
     */
    public function getParticipateHunts(): Collection
    {
        return $this->participateHunts;
    }

    public function addParticipateHunt(ParticipateHunt $participateHunt): static
    {
        if (!$this->participateHunts->contains($participateHunt)) {
            $this->participateHunts->add($participateHunt);
            $participateHunt->setHunt($this);
        }

        return $this;
    }

    public function removeParticipateHunt(ParticipateHunt $participateHunt): static
    {
        if ($this->participateHunts->removeElement($participateHunt)) {
            // set the owning side to null (unless already changed)
            if ($participateHunt->getHunt() === $this) {
                $participateHunt->setHunt(null);
            }
        }

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getDesignerTeam(): ?DesignerTeam
    {
        return $this->designerTeam;
    }

    public function setDesignerTeam(?DesignerTeam $designerTeam): static
    {
        $this->designerTeam = $designerTeam;

        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
