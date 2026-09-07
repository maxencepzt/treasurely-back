<?php

namespace App\Dto\Designer;

use App\Entity\Riddle;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une énigme telle que le formulaire de conception la soumet. Les règles métier
 * (bornes, cohérence du QCM, coordonnées) vivent sur les entités : ce DTO ne garantit
 * que la forme, pour que la correspondance vers l'entité soit sûre.
 */
final class RiddleInput
{
    public const array TYPES = ['text', 'gps', 'mcq', 'qr'];

    /**
     * @param string[] $choices
     * @param string[] $answers
     */
    public function __construct(
        #[Assert\Positive]
        public readonly ?int $id,
        #[Assert\Choice(choices: self::TYPES)]
        public readonly string $type,
        #[Assert\NotBlank]
        public readonly string $title,
        #[Assert\NotBlank]
        public readonly string $description,
        public readonly int $difficulty,
        public readonly int $maxScoringAttempts,
        public readonly ?string $answer,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly array $choices,
        public readonly array $answers,
        public readonly bool $revealAnswerCount,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;

        return new self(
            id: is_numeric($id) ? (int) $id : null,
            type: (string) ($data['type'] ?? ''),
            title: trim((string) ($data['title'] ?? '')),
            description: trim((string) ($data['description'] ?? '')),
            difficulty: (int) ($data['difficulty'] ?? 2),
            maxScoringAttempts: (int) ($data['maxScoringAttempts'] ?? Riddle::DEFAULT_MAX_SCORING_ATTEMPTS),
            answer: isset($data['answer']) ? trim((string) $data['answer']) : null,
            latitude: is_numeric($data['latitude'] ?? null) ? (float) $data['latitude'] : null,
            longitude: is_numeric($data['longitude'] ?? null) ? (float) $data['longitude'] : null,
            choices: self::stringList($data['choices'] ?? []),
            answers: self::stringList($data['answers'] ?? []),
            revealAnswerCount: filter_var($data['revealAnswerCount'] ?? true, \FILTER_VALIDATE_BOOLEAN),
        );
    }

    /**
     * @return string[]
     */
    private static function stringList(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $strings = [];
        foreach ($values as $value) {
            if (is_scalar($value)) {
                $strings[] = trim((string) $value);
            }
        }

        return array_values(array_filter($strings, static fn (string $s): bool => '' !== $s));
    }
}
