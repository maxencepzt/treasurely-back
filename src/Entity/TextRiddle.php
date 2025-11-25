<?php

namespace App\Entity;

use App\Repository\TextRiddleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TextRiddleRepository::class)]
class TextRiddle extends Riddle
{
    #[ORM\Column(length: 100)]
    private string $answer;

    #[Groups(['riddle:read'])]
    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): static
    {
        $this->answer = $answer;

        return $this;
    }
}
