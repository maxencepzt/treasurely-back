<?php

declare(strict_types=1);

namespace App\Enum;

enum Gender: string
{
    case MAN = 'HOMME';
    case WOMAN = 'FEMME';
    case OTHER = 'AUTRE';
}
