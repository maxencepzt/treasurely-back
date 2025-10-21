<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueUserNickname extends Constraint
{
    /**
     * @param string $message message to display when the nickname is not unique
     */
    public function __construct(
        public string $message = 'This nickname is already in use.',
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
