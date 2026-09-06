<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
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
#[ApiResource(
    operations: [
        new Get(
            openapi: new Operation(
                summary: 'Riddle details',
                description: 'Retrieve detailed information about a specific riddle by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['riddle:read']],
            security: "is_granted('ROLE_USER')",
        ),
    ]
)]
abstract class Riddle
{
    public const int MIN_SCORING_ATTEMPTS = 1;
    public const int MAX_SCORING_ATTEMPTS = 20;
    public const int DEFAULT_MAX_SCORING_ATTEMPTS = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['treasureHunt:riddles'])]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Groups(['treasureHunt:riddles', 'riddle:read'])]
    private string $title;

    #[ORM\Column(length: 1000)]
    #[Groups(['treasureHunt:riddles', 'riddle:read'])]
    private string $description;

    #[ORM\Column]
    #[Assert\Choice(
        choices: [1, 2, 3],
    )]
    #[Groups(['treasureHunt:riddles', 'riddle:read'])]
    private int $difficulty;

    #[Groups(['treasureHunt:riddles', 'riddle:read'])]
    #[ORM\Column]
    private int $orderNumber;

    /**
     * Nombre de soumissions pouvant rapporter des points. Au-delà, l'énigme reste
     * soumissible pour ne pas bloquer la progression, mais ne rapporte plus rien.
     */
    #[ORM\Column(options: ['default' => self::DEFAULT_MAX_SCORING_ATTEMPTS])]
    #[Assert\Range(min: self::MIN_SCORING_ATTEMPTS, max: self::MAX_SCORING_ATTEMPTS)]
    #[Groups(['treasureHunt:riddles', 'riddle:read'])]
    private int $maxScoringAttempts = self::DEFAULT_MAX_SCORING_ATTEMPTS;

    #[ORM\ManyToOne(inversedBy: 'riddles')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups('riddle:read')]
    private ?TreasureHunt $hunt = null;

    /**
     * @var Collection<int, ParticipateRiddle>
     */
    #[ORM\OneToMany(targetEntity: ParticipateRiddle::class, mappedBy: 'riddle', orphanRemoval: true)]
    private Collection $participateRiddles;

    /**
     * @var Collection<int, ParticipateHunt>
     */
    #[ORM\OneToMany(targetEntity: ParticipateHunt::class, mappedBy: 'currentRiddle', cascade: ['remove'], orphanRemoval: true)]
    private Collection $participateHunts;

    public function __construct()
    {
        $this->participateRiddles = new ArrayCollection();
        $this->participateHunts = new ArrayCollection();
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
            $participateHunt->setCurrentRiddle($this);
        }

        return $this;
    }

    public function removeParticipateHunt(ParticipateHunt $participateHunt): static
    {
        if ($this->participateHunts->removeElement($participateHunt)) {
            // set the owning side to null (unless already changed)
            if ($participateHunt->getCurrentRiddle() === $this) {
                $participateHunt->setCurrentRiddle(null);
            }
        }

        return $this;
    }

    public function getMaxScoringAttempts(): int
    {
        return $this->maxScoringAttempts;
    }

    public function setMaxScoringAttempts(int $maxScoringAttempts): static
    {
        $this->maxScoringAttempts = $maxScoringAttempts;

        return $this;
    }

    /**
     * Discriminant explicite du sous-type. La réponse JSON-LD annonce `@var: "Riddle"`
     * pour les quatre sous-types : sans ce champ, le client ne peut pas savoir quel
     * formulaire présenter une fois les solutions retirées de `riddle:read`.
     */
    #[Groups(['treasureHunt:riddles', 'riddle:read'])]
    abstract public function getType(): string;

    /**
     * Nombre de bonnes réponses attendues, pour les seuls QCM dont le concepteur a
     * choisi de le révéler. `null` signifie « non communiqué », jamais « aucune ».
     */
    #[Groups(['riddle:read'])]
    public function getExpectedAnswerCount(): ?int
    {
        return null;
    }

    #[Groups(['riddle:read'])]
    public function getCode(): ?string
    {
        return null;
    }

    /**
     * @return string[]|null
     */
    #[Groups(['riddle:read'])]
    public function getChoices(): ?array
    {
        return null;
    }

    /**
     * @return string[]|null
     */
    #[Groups(['riddle:read'])]
    public function getAnswers(): ?array
    {
        return null;
    }

    #[Groups(['riddle:read'])]
    public function getAnswer(): ?string
    {
        return null;
    }

    #[Groups(['riddle:read'])]
    public function getLatitude(): ?float
    {
        return null;
    }

    #[Groups(['riddle:read'])]
    public function getLongitude(): ?float
    {
        return null;
    }
}
