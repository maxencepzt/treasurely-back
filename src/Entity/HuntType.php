<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use App\Repository\HuntTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: HuntTypeRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            openapi: new Operation(
                summary: 'HuntType details',
                description: 'Retrieve detailed information about a specific hunt type by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['huntType:read']],
            security: "is_granted('ROLE_USER')",
        ),
    ]
)]
class HuntType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['huntType:read', 'huntType:id'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['huntType:read', 'huntType:write'])]
    private string $title;

    /**
     * @var Collection<int, TreasureHunt>
     */
    #[ORM\ManyToMany(targetEntity: TreasureHunt::class, inversedBy: 'huntType', cascade: ['persist'])]
    #[Groups(['huntType:write'])]
    #[ORM\JoinColumn(nullable: true)]
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
            $treasureHunt->addHuntType($this);
        }

        return $this;
    }

    public function removeTreasureHunt(TreasureHunt $treasureHunt): static
    {
        if ($this->treasureHunts->removeElement($treasureHunt)) {
            $treasureHunt->removeHuntType($this);
        }

        return $this;
    }
}
