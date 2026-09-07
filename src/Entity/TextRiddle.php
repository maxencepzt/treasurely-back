<?php

namespace App\Entity;

use App\Dto\RiddleAttempt;
use App\Repository\TextRiddleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\String\u;

#[ORM\Entity(repositoryClass: TextRiddleRepository::class)]
class TextRiddle extends Riddle
{
    public function getType(): string
    {
        return 'text';
    }

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $answer;

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): static
    {
        $this->answer = $answer;

        return $this;
    }

    /**
     * Comparaison indulgente : casse, accents et espaces superflus ne comptent pas.
     */
    public function accepts(RiddleAttempt $attempt): bool
    {
        if (null === $attempt->proposal) {
            throw new \InvalidArgumentException('Une réponse textuelle est attendue.');
        }

        return self::normalize($attempt->proposal) === self::normalize($this->answer);
    }

    private static function normalize(string $text): string
    {
        return u($text)->ascii()->lower()->collapseWhitespace()->toString();
    }
}
