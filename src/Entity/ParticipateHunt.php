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
            normalizationContext: ['groups' => ['participate_hunt:read', 'participate_hunt:id']],
            denormalizationContext: ['groups' => ['participate_hunt:write']],
            security: 'is_granted("ROLE_USER")',
        ),
        new Get(
            openapi: new Operation(
                summary: 'Hunt participation details',
                description: 'Retrieve detailed information about a specific hunt participation by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['participate_hunt:read']],
            security: "is_granted('ROLE_USER')",
        ),
        new Patch(
            openapi: new Operation(
                summary: 'Update hunt participation',
                description: 'Update a specific hunt participation by their ID.'
            ),
            normalizationContext: ['groups' => ['participate_hunt:read', 'participate_hunt:id']],
            denormalizationContext: ['groups' => ['participate_hunt:write']],
            security: "is_granted('ROLE_USER')"
        ),
    ]
)]
class ParticipateHunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Choice(choices: [0, 1, 2, 3, 4, 5])]
    #[Groups(['playerTeam:treasureHunts'])]
    private ?int $rate = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['playerTeam:treasureHunts'])]
    private int $time = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['playerTeam:treasureHunts'])]
    private int $score;

    #[ORM\Column]
    #[Groups(['playerTeam:treasureHunts'])]
    private bool $finished = false;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['playerTeam:treasureHunts'])]
    private \DateTimeImmutable $lastParticipate;

    #[ORM\ManyToOne(inversedBy: 'participateHunts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['playerTeam:treasureHunts'])]
    private User $hunter;

    #[ORM\ManyToOne(inversedBy: 'participateHunts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['playerTeam:treasureHunts'])]
    private TreasureHunt $hunt;

    #[ORM\ManyToOne(inversedBy: 'participateHunts')]
    private ?PlayerTeam $playerTeam = null;

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
}
