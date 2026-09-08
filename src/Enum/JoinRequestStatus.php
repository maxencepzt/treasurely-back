<?php

declare(strict_types=1);

namespace App\Enum;

enum JoinRequestStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REFUSED = 'refused';
}
