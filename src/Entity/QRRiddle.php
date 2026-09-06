<?php

namespace App\Entity;

use App\Repository\QRRiddleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QRRiddleRepository::class)]
class QRRiddle extends Riddle
{
    public function getType(): string
    {
        return 'qr';
    }

    #[ORM\Column(length: 20)]
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
}
