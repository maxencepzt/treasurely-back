<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use App\Repository\ParticipateRiddleRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipateRiddleRepository::class)]
/**
 * Progression d'un joueur sur une énigme. Créée à la lecture de l'énigme, écrite par le seul
 * serveur au fil des soumissions à POST /riddles/{id}/attempt : le client la lit, jamais ne l'écrit.
 */
#[ApiResource(
    operations: [
        new Get(
            openapi: new Operation(
                summary: 'Riddle participation details',
                description: 'Retrieve detailed information about a specific riddle participation by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['participateRiddle:read']],
            security: "(is_granted('ROLE_USER') and object.getHunter() == user) or is_granted('ROLE_ADMIN')",
        ),
    ]
)]
class ParticipateRiddle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['participateRiddle:read', 'participateRiddle:id'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['participateRiddle:read'])]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(nullable: true)]
    #[Groups(['participateRiddle:read'])]
    private ?\DateTimeImmutable $finishTime = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['participateRiddle:read'])]
    private int $score = 0;

    #[ORM\Column]
    #[Groups(['participateRiddle:read'])]
    private \DateTime $lastParticipate;

    /**
     * Soumissions déjà faites sur cette énigme, correctes ou non. Comparé à
     * {@see Riddle::getMaxScoringAttempts()} pour décider si la réussite rapporte des points.
     */
    #[ORM\Column(options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['participateRiddle:read'])]
    private int $attempts = 0;

    #[ORM\ManyToOne(inversedBy: 'participateRiddles')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['participateRiddle:read'])]
    private ?User $hunter = null;

    #[ORM\ManyToOne(inversedBy: 'participateRiddles')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['participateRiddle:read'])]
    private ?Riddle $riddle = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getFinishTime(): ?\DateTimeImmutable
    {
        return $this->finishTime;
    }

    public function setFinishTime(?\DateTimeImmutable $finishTime): static
    {
        $this->finishTime = $finishTime;

        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getLastParticipate(): \DateTime
    {
        return $this->lastParticipate;
    }

    public function setLastParticipate(\DateTime $lastParticipate): static
    {
        $this->lastParticipate = $lastParticipate;

        return $this;
    }

    public function getHunter(): User
    {
        return $this->hunter;
    }

    public function setHunter(?User $hunter): static
    {
        $this->hunter = $hunter;

        return $this;
    }

    public function getRiddle(): Riddle
    {
        return $this->riddle;
    }

    public function setRiddle(?Riddle $riddle): static
    {
        $this->riddle = $riddle;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            'Participation #%d - %s',
            $this->id,
            $this->riddle ? $this->riddle->getTitle() : 'Aucune énigme'
        );
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): static
    {
        $this->attempts = $attempts;

        return $this;
    }

    public function incrementAttempts(): static
    {
        ++$this->attempts;

        return $this;
    }

    #[Groups(['participateRiddle:read'])]
    public function isSolved(): bool
    {
        return null !== $this->finishTime;
    }

    /**
     * Essais pouvant encore rapporter des points, annoncés au joueur après chaque réponse.
     */
    #[Groups(['participateRiddle:read'])]
    public function getAttemptsRemaining(): int
    {
        return max(0, $this->getRiddle()->getMaxScoringAttempts() - $this->attempts);
    }
}
