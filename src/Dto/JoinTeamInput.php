<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Le code de jointure reçu du créateur d'une équipe de joueurs. */
final class JoinTeamInput
{
    #[Assert\NotBlank(message: 'Le code est obligatoire.')]
    public string $code = '';
}
