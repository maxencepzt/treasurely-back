<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

final class MeGetCest
{
    /**
     * @return array<string, string>
     */
    protected static function expectedProperties(): array
    {
        return [
            'id' => 'integer',
            'nickname' => 'string',
            'firstname' => 'string',
            'lastname' => 'string',
            'email' => 'string:email',
            'birthDate' => 'string:date',
            'phone' => 'string',
            'creationDate' => 'string:date',
            'public' => 'boolean',
            'gender' => 'string',
            'profilePicture' => 'string|null',
            'totalTime' => 'integer',
            'totalHunt' => 'integer',
        ];
    }
}
