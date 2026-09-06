<?php

namespace App\Entity;

use App\Repository\GPSRiddleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GPSRiddleRepository::class)]
class GPSRiddle extends Riddle
{
    #[ORM\Column]
    #[Assert\Range(min: -90, max: 90)]
    private float $latitude;

    #[ORM\Column]
    #[Assert\Range(min: -180, max: 180)]
    private float $longitude;

    public function getType(): string
    {
        return 'gps';
    }

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
