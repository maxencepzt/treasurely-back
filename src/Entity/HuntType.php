<?php

namespace App\Entity;

use App\Repository\HuntTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HuntTypeRepository::class)]
class HuntType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $title;

    /**
     * @var Collection<int, TreasureHunt>
     */
    #[ORM\ManyToMany(targetEntity: TreasureHunt::class, mappedBy: 'huntTypeId')]
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
            $treasureHunt->addHuntTypeId($this);
        }

        return $this;
    }

    public function removeTreasureHunt(TreasureHunt $treasureHunt): static
    {
        if ($this->treasureHunts->removeElement($treasureHunt)) {
            $treasureHunt->removeHuntTypeId($this);
        }

        return $this;
    }
}
