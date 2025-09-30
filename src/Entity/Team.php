<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'ownedTeams')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $owner = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Picture $image = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'teams')]
    private Collection $members;

    /**
     * @var Collection<int, TreasureHunt>
     */
    #[ORM\OneToMany(targetEntity: TreasureHunt::class, mappedBy: 'teamId', orphanRemoval: true)]
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
