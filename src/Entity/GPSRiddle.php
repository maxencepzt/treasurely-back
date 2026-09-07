<?php

namespace App\Entity;

use App\Dto\RiddleAttempt;
use App\Repository\GPSRiddleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GPSRiddleRepository::class)]
class GPSRiddle extends Riddle
{
    /** Rayon de validation, en mètres : la précision d'un téléphone en ville, entre bâtiments. */
    public const int TOLERANCE_METERS = 50;

    private const float EARTH_RADIUS_METERS = 6_371_000.0;

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

    /**
     * La position transmise reste déclarative : rien ne l'authentifie côté serveur.
     */
    public function accepts(RiddleAttempt $attempt): bool
    {
        if (null === $attempt->latitude || null === $attempt->longitude) {
            throw new \InvalidArgumentException('Une latitude et une longitude sont attendues.');
        }

        return $this->distanceTo($attempt->latitude, $attempt->longitude) <= self::TOLERANCE_METERS;
    }

    /**
     * Distance orthodromique (formule de haversine), en mètres.
     */
    private function distanceTo(float $latitude, float $longitude): float
    {
        $dLat = deg2rad($latitude - $this->latitude);
        $dLon = deg2rad($longitude - $this->longitude);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) * sin($dLon / 2) ** 2;

        return 2 * self::EARTH_RADIUS_METERS * asin(sqrt($a));
    }
}
