<?php

namespace App\Entity;

use App\Repository\DesignerTeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DesignerTeamRepository::class)]
class DesignerTeam extends Team
{
    /**
     * @var Collection<int, TreasureHunt>
     */
    #[ORM\OneToMany(targetEntity: TreasureHunt::class, mappedBy: 'designerTeam', orphanRemoval: true)]
    private Collection $hunts;

    public function __construct()
    {
        parent::__construct();
        $this->hunts = new ArrayCollection();
    }

    /**
     * @return Collection<int, TreasureHunt>
     */
    public function getHunts(): Collection
    {
        return $this->hunts;
    }

    public function addHunt(TreasureHunt $hunt): static
    {
        if (!$this->hunts->contains($hunt)) {
            $this->hunts->add($hunt);
            $hunt->setDesignerTeam($this);
        }

        return $this;
    }

    public function removeHunt(TreasureHunt $hunt): static
    {
        if ($this->hunts->removeElement($hunt)) {
            // set the owning side to null (unless already changed)
            if ($hunt->getDesignerTeam() === $this) {
                $hunt->setDesignerTeam(null);
            }
        }

        return $this;
    }

    public function getCode(): null
    {
        return null;
    }
}
