<?php

namespace App\Entity;

use App\Repository\PictureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PictureRepository::class)]
class Picture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // TODO: Revoir le typage de $image, actuellement non typé correctement pour PHPStan.
    #[ORM\Column(type: Types::BLOB)]
    /** @phpstan-ignore-next-line Le type n'est pas compatible avec PHPStan (Types::BLOB) */
    private $image;

    public function getId(): ?int
    {
        return $this->id;
    }

    /** @phpstan-ignore-next-line Le type n'est pas compatible avec PHPStan (Types::BLOB) */
    public function getImage()
    {
        return $this->image;
    }

    /** @phpstan-ignore-next-line Le type n'est pas compatible avec PHPStan (Types::BLOB) */
    public function setImage($image): static
    {
        $this->image = $image;

        return $this;
    }
}
