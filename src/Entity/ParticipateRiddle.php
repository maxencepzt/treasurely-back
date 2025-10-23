<?php

namespace App\Entity;

use App\Repository\ParticipateRiddleRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipateRiddleRepository::class)]
class ParticipateRiddle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\LessThanOrEqual('today')]
    #[Gedmo\Timestampable(on: 'create')]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(nullable: true)]
    #[Assert\LessThanOrEqual('today')]
    private ?\DateTimeImmutable $finishTime = null;

    #[ORM\Column]
    #[Assert\Positive]
    private int $score;

    #[ORM\Column]
    #[Assert\LessThanOrEqual('today')]
    private \DateTime $lastParticipate;

    #[ORM\ManyToOne(inversedBy: 'participateRiddles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $hunter = null;

    #[ORM\ManyToOne(inversedBy: 'participateRiddles')]
    #[ORM\JoinColumn(nullable: true)]
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

    public function getFinishTime(): \DateTimeImmutable
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
}
