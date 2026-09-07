<?php

namespace App\Entity;

use App\Dto\RiddleAttempt;
use App\Repository\QRRiddleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QRRiddleRepository::class)]
class QRRiddle extends Riddle
{
    public function getType(): string
    {
        return 'qr';
    }

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private string $code;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Le code n'étant jamais publié, le connaître vaut preuve de scan.
     */
    public function accepts(RiddleAttempt $attempt): bool
    {
        if (null === $attempt->proposal) {
            throw new \InvalidArgumentException('Le contenu du QR code est attendu.');
        }

        return trim($attempt->proposal) === $this->code;
    }
}
