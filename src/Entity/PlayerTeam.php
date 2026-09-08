<?php

namespace App\Entity;

use App\Repository\PlayerTeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PlayerTeamRepository::class)]
#[ORM\HasLifecycleCallbacks]
// Le code de jointure identifie l'équipe auprès des joueurs : deux équipes ne peuvent pas le partager.
#[UniqueEntity(fields: ['code'], message: 'Ce code est déjà utilisé par une autre équipe.')]
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

    /**
     * Une participation survit à son équipe : elle est détachée avant la suppression, côté ORM,
     * pour que le graphe reste cohérent quel que soit le SGBD (la base fait de même par `SET NULL`).
     */
    #[ORM\PreRemove]
    public function detachParticipations(): void
    {
        foreach ($this->participateHunts as $participateHunt) {
            $participateHunt->setPlayerTeam(null);
        }
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /** Un code de jointure neuf : `treasurely_` suivi de treize chiffres, tiré au sort. */
    public static function generateCode(): string
    {
        return 'treasurely_'.str_pad((string) random_int(0, 9_999_999_999_999), 13, '0', STR_PAD_LEFT);
    }

    public function getType(): string
    {
        return 'player';
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
