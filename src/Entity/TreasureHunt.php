<?php

namespace App\Entity;

use App\Repository\TreasureHuntRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TreasureHuntRepository::class)]
class TreasureHunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $public;

    #[ORM\Column]
    private ?int $difficulty;

    #[ORM\Column]
    private ?int $riddleCount;

    /**
     * @var Collection<int, HuntType>
     */
    #[ORM\ManyToMany(targetEntity: HuntType::class, inversedBy: 'treasureHunts')]
    private Collection $huntType;

    #[ORM\ManyToOne(inversedBy: 'treasureHunts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Team $team;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Picture $image = null;

    #[ORM\ManyToOne(inversedBy: 'treasureHunts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner;

    public function __construct()
    {
        $this->huntType = new ArrayCollection();
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
        }

        return $this;
    }

    public function removeHuntType(HuntType $huntType): static
    {
        $this->huntType->removeElement($huntType);

        return $this;
    }

    public function getTeamId(): ?Team
    {
        return $this->team;
    }

    public function setTeamId(?Team $team): static
    {
        $this->team = $team;

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
}
