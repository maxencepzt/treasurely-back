<?php

namespace App\Service\Designer\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * La charge utile ou les entités qui en résultent violent une contrainte. Porte les
 * messages tels qu'ils peuvent être montrés au concepteur.
 */
final class HuntValidationException extends \InvalidArgumentException
{
    /**
     * @param list<string> $messages
     */
    public function __construct(private readonly array $messages)
    {
        parent::__construct(implode(' ', $messages));
    }

    public static function fromViolations(ConstraintViolationListInterface $violations): self
    {
        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = (string) $violation->getMessage();
        }

        return new self(array_values(array_unique($messages)));
    }

    /**
     * @return list<string>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
