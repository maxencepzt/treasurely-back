<?php

namespace App\Entity;

use App\Repository\QRRiddleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QRRiddleRepository::class)]
class QRRiddle extends Riddle
{
    #[ORM\Column(length: 20)]
    private ?string $code = null;

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
