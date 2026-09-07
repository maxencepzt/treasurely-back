<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Proposition d'un joueur pour une énigme. Un seul corps pour les quatre types :
 * chaque sous-type de {@see \App\Entity\Riddle} lit le champ qui le concerne.
 */
final class RiddleAttempt
{
    /** Texte saisi ou code lu (énigmes texte et QR). */
    #[Assert\Length(max: 100)]
    public ?string $proposal = null;

    /**
     * Choix cochés (QCM).
     *
     * @var string[]|null
     */
    #[Assert\Count(max: 20)]
    #[Assert\All([new Assert\Type('string'), new Assert\Length(max: 100)])]
    public ?array $choices = null;

    #[Assert\Range(min: -90, max: 90)]
    public ?float $latitude = null;

    #[Assert\Range(min: -180, max: 180)]
    public ?float $longitude = null;
}
