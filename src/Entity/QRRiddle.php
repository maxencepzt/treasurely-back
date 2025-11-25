<?php

namespace App\Entity;

use App\Repository\QRRiddleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: QRRiddleRepository::class)]
class QRRiddle extends Riddle
{
    #[ORM\Column(length: 20)]
    private string $code;

    #[Groups(['riddle:read'])]
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
