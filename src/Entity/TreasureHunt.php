<?php

namespace App\Entity;

use App\Repository\TreasureHuntRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TreasureHuntRepository::class)]
class TreasureHunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $title;

    #[ORM\Column(length: 3000, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $public = true;

    #[ORM\Column]
    #[Assert\Choice(
        choices: [1, 2, 3],
    )]
    private int $difficulty;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $riddleCount;

    /**
     * @var Collection<int, HuntType>
     */
    #[ORM\ManyToMany(targetEntity: HuntType::class, mappedBy: 'treasureHunts')]
    private Collection $huntType;

    #[ORM\ManyToOne(inversedBy: 'treasureHunts')]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Picture $image = null;

    #[ORM\ManyToOne(inversedBy: 'treasureHunts')]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    /**
     * @var Collection<int, Riddle>
     */
    #[ORM\OneToMany(targetEntity: Riddle::class, mappedBy: 'hunt', orphanRemoval: true)]
    private Collection $riddles;

    public function __construct()
    {
        $this->huntType = new ArrayCollection();
        $this->riddles = new ArrayCollection();
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

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
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
}
