<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ce que le joueur choisit en créant son équipe : le nom et une description. Le serveur
 * fixe le créateur, le code de jointure et le premier membre.
 */
final class PlayerTeamInput
{
    #[Assert\NotBlank(message: 'Le nom de l\'équipe est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    public string $name = '';

    #[Assert\Length(max: 500, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    public ?string $description = null;
}
