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
            normalizationContext: ['groups' => ['participate_riddle:read', 'participate_riddle:id']],
            denormalizationContext: ['groups' => ['participate_riddle:write']],
            security: 'is_granted("ROLE_USER")',
        ),
        new Get(
            openapi: new Operation(
                summary: 'Riddle participation details',
                description: 'Retrieve detailed information about a specific riddle participation by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['participate_riddle:read']],
            security: "is_granted('ROLE_USER')",
        ),
        new Patch(
            openapi: new Operation(
                summary: 'Update riddle participation',
                description: 'Update a specific riddle participation by their ID.'
            ),
            normalizationContext: ['groups' => ['participate_riddle:read', 'participate_riddle:id']],
            denormalizationContext: ['groups' => ['participate_riddle:write']],
            security: "is_granted('ROLE_USER')"
        ),
    ]
)]
class ParticipateRiddle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['participate_riddle:read', 'participate_riddle:id'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\LessThanOrEqual('today')]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['participate_riddle:read'])]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(nullable: true)]
    #[Assert\LessThanOrEqual('today')]
    #[Groups(['participate_riddle:read', 'participate_riddle:write'])]
    private ?\DateTimeImmutable $finishTime = null;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['participate_riddle:read', 'participate_riddle:write'])]
    private int $score;

    #[ORM\Column]
    #[Assert\LessThanOrEqual('today')]
    #[Groups(['participate_riddle:read', 'participate_riddle:write'])]
    private \DateTime $lastParticipate;

    #[ORM\ManyToOne(inversedBy: 'participateRiddles')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['participate_riddle:read'])]
    private ?User $hunter = null;

    #[ORM\ManyToOne(inversedBy: 'participateRiddles')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['participate_riddle:read'])]
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
}
