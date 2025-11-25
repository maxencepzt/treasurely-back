<?php

namespace App\Entity;

use App\Repository\MCQRiddleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MCQRiddleRepository::class)]
class MCQRiddle extends Riddle
{
    /**
     * @var string[]
     */
    #[ORM\Column(type: Types::ARRAY)]
    private array $choices = [];

    /**
     * @var string[]
     */
    #[ORM\Column(type: Types::ARRAY)]
    private array $answers = [];

    /**
     * @return string[]
     */
    #[Groups(['riddle:read'])]
    public function getChoices(): array
    {
        return $this->choices;
    }

    /**
     * @param string[] $choices
     *
     * @return $this
     */
    public function setChoices(array $choices): static
    {
        $this->choices = $choices;

        return $this;
    }

    /**
     * @return string[]
     */
    #[Groups(['riddle:read'])]
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /**
     * @param string[] $answers
     *
     * @return $this
     */
    public function setAnswers(array $answers): static
    {
        $this->answers = $answers;

        return $this;
    }
}
