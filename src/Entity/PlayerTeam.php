<?php

namespace App\Entity;

use App\Repository\PlayerTeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerTeamRepository::class)]
class PlayerTeam extends Team
{
    #[ORM\Column(length: 24)]
    private string $code;

    /**
     * @var Collection<int, ParticipateHunt>
     */
    #[ORM\OneToMany(targetEntity: ParticipateHunt::class, mappedBy: 'playerTeam')]
    private Collection $participateHunts;

    public function __construct()
    {
        parent::__construct();
        $this->participateHunts = new ArrayCollection();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

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
            $participateHunt->setPlayerTeam($this);
        }

        return $this;
    }

    public function removeParticipateHunt(ParticipateHunt $participateHunt): static
    {
        if ($this->participateHunts->removeElement($participateHunt)) {
            // set the owning side to null (unless already changed)
            if ($participateHunt->getPlayerTeam() === $this) {
                $participateHunt->setPlayerTeam(null);
            }
        }

        return $this;
    }
}
