<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Repository\ParticipateHuntRepository;
use App\State\JoinHuntProcessor;
use App\State\ReplayHuntProcessor;
use App\State\ScoreboardProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipateHuntRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            openapi: new Operation(
                summary: 'Join a treasure hunt',
                description: 'Start playing an opened treasure hunt, alone or for one of your player teams. The server sets the hunter and the first riddle. Designers cannot join their own hunt.'
            ),
            normalizationContext: ['groups' => ['participateHunt:read', 'participateHunt:id']],
            denormalizationContext: ['groups' => ['participateHunt:create']],
            security: 'is_granted("ROLE_USER")',
            processor: JoinHuntProcessor::class,
        ),
        new Get(
            openapi: new Operation(
                summary: 'Hunt participation details',
                description: 'Retrieve detailed information about a specific hunt participation by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['participateHunt:read']],
            security: "(is_granted('ROLE_USER') and object.getHunter() == user) or is_granted('ROLE_ADMIN')",
        ),
        new Post(
            uriTemplate: '/participate_hunts/{id}/replay',
            status: 200,
            openapi: new Operation(
                summary: 'Replay a finished treasure hunt',
                description: 'Start your own finished participation over: back to the first riddle, score and time at zero, riddle clocks cleared. The previous score is not kept, the next one replaces it. The hunt must be opened.'
            ),
            normalizationContext: ['groups' => ['participateHunt:read', 'participateHunt:id']],
            security: "is_granted('ROLE_USER')",
            deserialize: false,
            validate: false,
            processor: ReplayHuntProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/treasure_hunts/{id}/scoreboard',
            uriVariables: ['id' => new Link(fromClass: TreasureHunt::class, toProperty: 'hunt')],
            openapi: new Operation(
                summary: 'Scoreboard of a treasure hunt',
                description: 'The ten finishers to compare with: the top ten for a viewer who never finished the hunt, otherwise a window of ten around the viewer (five above, four below, shifted at both ends). Ranked by score, then by time, then by finish date.'
            ),
            normalizationContext: ['groups' => ['participateHunt:scoreboard']],
            paginationEnabled: false,
            security: "is_granted('ROLE_USER')",
            provider: ScoreboardProvider::class,
        ),
        new Patch(
            openapi: new Operation(
                summary: 'Rate a hunt participation',
                description: 'The rate is the only field a player writes: progression, score and time are computed by the server.'
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

    /** Temps de résolution cumulé, en secondes, recalculé par le serveur à chaque énigme résolue. */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'user:participations', 'participateHunt:scoreboard'])]
    private int $time = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'user:participations', 'participateHunt:scoreboard'])]
    private int $score = 0;

    /**
     * Place dans le classement de la chasse, posée par {@see ScoreboardProvider} : elle
     * dépend des autres participations, pas de celle-ci, et n'est donc pas stockée.
     */
    #[Groups(['participateHunt:scoreboard'])]
    private ?int $rank = null;

    #[ORM\Column]
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'user:participations'])]
    private bool $finished = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'user:participations', 'participateHunt:scoreboard'])]
    private \DateTimeImmutable $lastParticipate;

    #[ORM\ManyToOne(inversedBy: 'participateHunts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'user:participations', 'participateHunt:scoreboard'])]
    #[MaxDepth(1)]
    private User $hunter;

    #[ORM\ManyToOne(inversedBy: 'participateHunts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[MaxDepth(1)]
    #[Assert\NotNull(message: 'La chasse est obligatoire.')]
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'participateHunt:create', 'user:participations'])]
    private TreasureHunt $hunt;

    #[MaxDepth(1)]
    #[ORM\ManyToOne(inversedBy: 'participateHunts')]
    #[Groups(['participateHunt:read', 'participateHunt:create', 'user:participations', 'participateHunt:scoreboard'])]
    private ?PlayerTeam $playerTeam = null;

    #[MaxDepth(1)]
    #[ORM\ManyToOne(inversedBy: 'participateHunts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['participateHunt:read', 'user:participations'])]
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

    /**
     * Progression affichable sans lire l'énigme en cours, dont la lecture démarre le chronomètre.
     */
    #[Groups(['participateHunt:read', 'playerTeam:treasureHunts', 'user:participations'])]
    public function getRiddlesSolved(): int
    {
        return $this->finished ? ($this->hunt->getRiddleCount() ?? 0) : $this->currentRiddle->getOrderNumber() - 1;
    }

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function setRank(?int $rank): static
    {
        $this->rank = $rank;

        return $this;
    }

    public function setCurrentRiddle(?Riddle $currentRiddle): static
    {
        $this->currentRiddle = $currentRiddle;

        return $this;
    }
}
