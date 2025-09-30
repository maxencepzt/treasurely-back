<?php

namespace App\Entity;

use App\Repository\ParticipateHuntRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipateHuntRepository::class)]
class ParticipateHunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Choice(choices: [0, 1, 2, 3, 4, 5])]
    private ?int $rate = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $time = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $score;

    #[ORM\Column]
    private bool $finished = false;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $lastParticipate;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private User $hunter;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private TreasureHunt $hunt;

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

    public function setHunter(User $hunter): static
    {
        $this->hunter = $hunter;

        return $this;
    }

    public function getHunt(): ?TreasureHunt
    {
        return $this->hunt;
    }

    public function setHunt(TreasureHunt $hunt): static
    {
        $this->hunt = $hunt;

        return $this;
    }
}
