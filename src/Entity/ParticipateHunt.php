<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Repository\ParticipateHuntRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipateHuntRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: 'participate_hunts/new',
            openapi: new Operation(
                summary: 'Hunt participation creation',
                description: 'Create a new participation record for a treasure hunt.'
            ),
            normalizationContext: ['groups' => ['participateHunt:read', 'participateHunt:id']],
            denormalizationContext: ['groups' => ['participateHunt:create']],
            security: 'is_granted("ROLE_USER")',
        ),
        new Get(
            openapi: new Operation(
                summary: 'Hunt participation details',
                description: 'Retrieve detailed information about a specific hunt participation by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['participateHunt:read']],
            security: "(is_granted('ROLE_USER') and object.getHunter() == user) or is_granted('ROLE_ADMIN')",
        ),
        new Patch(
            openapi: new Operation(
                summary: 'Update hunt participation',
                description: 'Update a specific hunt participation by their ID.'
            ),
            normalizationContext: ['groups' => ['participateHunt:read', 'participateHunt:id']],
            denormalizationContext: ['groups' => ['participateHunt:patch']],
            security: "(is_granted('ROLE_USER') and object.getHunter() == user) or is_granted('ROLE_ADMIN')",
        ),
    ]
)]
class ParticipateHunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['participateHunt:read', 'participateHunt:id', 'user:participations'])]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Choice(choices: [0, 1, 2, 3, 4, 5])]
    #[Groups(['participateHunt:read', 'participateHunt:patch', 'playerTeam:treasureHunts', 'user:participations'])]
    private ?int $rate = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'participateHunt:patch', 'user:participations'])]
    private int $time = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'participateHunt:patch', 'user:participations'])]
    private int $score = 0;

    #[ORM\Column]
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'participateHunt:patch', 'user:participations'])]
    private bool $finished = false;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'participateHunt:patch', 'participateHunt:create', 'user:participations'])]
    private \DateTimeImmutable $lastParticipate;

    #[ORM\ManyToOne(inversedBy: 'participateHunts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'participateHunt:create', 'user:participations'])]
    private User $hunter;

    #[ORM\ManyToOne(inversedBy: 'participateHunts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'participateHunt:create', 'user:participations'])]
    private TreasureHunt $hunt;

    #[ORM\ManyToOne(inversedBy: 'participateHunts')]
    #[Groups(['participateHunt:read', 'participateHunt:create', 'user:participations'])]
    private ?PlayerTeam $playerTeam = null;

    #[ORM\ManyToOne(inversedBy: 'participateHunts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['participateHunt:read', 'participateHunt:patch', 'user:participations'])]
    private Riddle $currentRiddle;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRate(): ?int
    {
        return $this->rate;
    }

    public function setRate(?int $rate): static
    {
        $this->rate = $rate;

        return $this;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function setTime(int $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function isFinished(): ?bool
    {
        return $this->finished;
    }

    public function setFinished(bool $finished): static
    {
        $this->finished = $finished;

        return $this;
    }

    public function getLastParticipate(): ?\DateTimeImmutable
    {
        return $this->lastParticipate;
    }

    public function setLastParticipate(\DateTimeImmutable $lastParticipate): static
    {
        $this->lastParticipate = $lastParticipate;

        return $this;
    }

    public function getHunter(): ?User
    {
        return $this->hunter;
    }

    public function setHunter(?User $hunter): static
    {
        $this->hunter = $hunter;

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

    public function getPlayerTeam(): ?PlayerTeam
    {
        return $this->playerTeam;
    }

    public function setPlayerTeam(?PlayerTeam $playerTeam): static
    {
        $this->playerTeam = $playerTeam;

        return $this;
    }

    public function getCurrentRiddle(): Riddle
    {
        return $this->currentRiddle;
    }

    public function setCurrentRiddle(?Riddle $currentRiddle): static
    {
        $this->currentRiddle = $currentRiddle;

        return $this;
    }
}
