<?php

namespace App\Entity;

use App\Repository\RiddleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\InheritanceType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RiddleRepository::class)]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discriminator', type: 'string')]
#[DiscriminatorMap([
    'qrRiddle' => QRRiddle::class,
    'mcqRiddle' => MCQRiddle::class,
    'textRiddle' => TextRiddle::class,
    'gpsRiddle' => GPSRiddle::class,
])]
class Riddle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['treasureHunt:riddles'])]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Groups(['treasureHunt:riddles'])]
    private string $title;

    #[ORM\Column(length: 1000)]
    #[Groups(['treasureHunt:riddles'])]
    private string $description;

    #[ORM\Column]
    #[Assert\Choice(
        choices: [1, 2, 3],
    )]
    #[Groups(['treasureHunt:riddles'])]
    private int $difficulty;

    #[ORM\Column]
    private int $orderNumber;

    #[ORM\ManyToOne(inversedBy: 'riddles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?TreasureHunt $hunt = null;

    /**
     * @var Collection<int, ParticipateRiddle>
     */
    #[ORM\OneToMany(targetEntity: ParticipateRiddle::class, mappedBy: 'riddle', orphanRemoval: true)]
    private Collection $participateRiddles;

    public function __construct()
    {
        $this->participateRiddles = new ArrayCollection();
    }

    public function getId(): int
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDifficulty(): int
    {
        return $this->difficulty;
    }

    public function setDifficulty(int $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getOrderNumber(): int
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(int $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getHunt(): ?TreasureHunt
    {
        return $this->hunt;
    }

    public function setHunt(?TreasureHunt $hunt): static
    {
        $this->hunt = $hunt;

        return $this;
    }

    /**
     * @return Collection<int, ParticipateRiddle>
     */
    public function getParticipateRiddles(): Collection
    {
        return $this->participateRiddles;
    }

    public function addParticipateRiddle(ParticipateRiddle $participateRiddle): static
    {
        if (!$this->participateRiddles->contains($participateRiddle)) {
            $this->participateRiddles->add($participateRiddle);
            $participateRiddle->setRiddle($this);
        }

        return $this;
    }

    public function removeParticipateRiddle(ParticipateRiddle $participateRiddle): static
    {
        if ($this->participateRiddles->removeElement($participateRiddle)) {
            // set the owning side to null (unless already changed)
            if ($participateRiddle->getRiddle() === $this) {
                $participateRiddle->setRiddle(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
