<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Repository\ParticipateRiddleRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipateRiddleRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: 'participate_riddles/new',
            openapi: new Operation(
                summary: 'Riddle participation creation',
                description: 'Create a new participation record for a treasure riddle.'
            ),
            normalizationContext: ['groups' => ['participateRiddle:read', 'participateRiddle:id']],
            denormalizationContext: ['groups' => ['participateRiddle:create']],
            security: 'is_granted("ROLE_USER")',
        ),
        new Get(
            openapi: new Operation(
                summary: 'Riddle participation details',
                description: 'Retrieve detailed information about a specific riddle participation by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['participateRiddle:read']],
            security: "(is_granted('ROLE_USER') and object.getHunter() == user) or is_granted('ROLE_ADMIN')",
        ),
        new Patch(
            openapi: new Operation(
                summary: 'Update riddle participation',
                description: 'Update a specific riddle participation by their ID.'
            ),
            normalizationContext: ['groups' => ['participateRiddle:read', 'participateRiddle:id']],
            denormalizationContext: ['groups' => ['participateRiddle:patch']],
            security: "(is_granted('ROLE_USER') and object.getHunter() == user) or is_granted('ROLE_ADMIN')"
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
    #[Assert\GreaterThanOrEqual('today')]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['participateRiddle:read'])]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(nullable: true)]
    #[Assert\GreaterThanOrEqual('today')]
    #[Groups(['participateRiddle:read', 'participateRiddle:patch'])]
    private ?\DateTimeImmutable $finishTime = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['participateRiddle:read', 'participateRiddle:create', 'participateRiddle:patch'])]
    private int $score = 0;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual('today')]
    #[Groups(['participateRiddle:read', 'participateRiddle:create', 'participateRiddle:patch'])]
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
    #[Groups(['participateRiddle:read', 'participateRiddle:create'])]
    private ?User $hunter = null;

    #[ORM\ManyToOne(inversedBy: 'participateRiddles')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['participateRiddle:read', 'participateRiddle:create'])]
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
