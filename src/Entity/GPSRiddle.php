<?php

namespace App\Entity;

use App\Repository\GPSRiddleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GPSRiddleRepository::class)]
class GPSRiddle extends Riddle
{
    #[ORM\Column]
    private float $latitude;

    #[ORM\Column]
    private float $longitude;

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }
}
