<?php

namespace App\Dto\Designer;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Charge utile du formulaire de conception d'une chasse, telle qu'envoyée en FormData
 * par hunt-form.js. Ne porte que les contraintes de forme ; le reste est validé sur
 * les entités une fois la correspondance faite.
 */
final class HuntInput
{
    public const string ACTION_DRAFT = 'draft';
    public const string ACTION_PUBLISH = 'publish';

    /**
     * @param int[]         $huntTypeIds
     * @param RiddleInput[] $riddles
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom de la chasse est requis.')]
        public readonly string $title,
        #[Assert\NotBlank(message: 'La description est requise.')]
        public readonly string $description,
        #[Assert\Positive(message: "L'équipe est requise.")]
        public readonly int $designerTeamId,
        public readonly array $huntTypeIds,
        public readonly int $difficulty,
        public readonly int $estimatedTime,
        #[Assert\NotBlank(message: 'La ville est requise.')]
        public readonly string $location,
        #[Assert\Choice(choices: [self::ACTION_DRAFT, self::ACTION_PUBLISH])]
        public readonly string $action,
        #[Assert\Valid]
        public readonly array $riddles,
        public readonly ?UploadedFile $image,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $riddles = self::decodeJsonList($request->request->get('riddles'));
        $image = $request->files->get('image');

        return new self(
            title: trim((string) $request->request->get('name', '')),
            description: trim((string) $request->request->get('description', '')),
            designerTeamId: (int) $request->request->get('designer_team_id', 0),
            huntTypeIds: array_map('intval', self::decodeJsonList($request->request->get('hunt_types'))),
            difficulty: (int) $request->request->get('difficulty', 0),
            estimatedTime: (int) $request->request->get('estimated_duration', 0),
            location: trim((string) $request->request->get('city', '')),
            action: (string) $request->request->get('action', self::ACTION_DRAFT),
            riddles: array_map(
                static fn (array $data): RiddleInput => RiddleInput::fromArray($data),
                array_filter($riddles, 'is_array'),
            ),
            image: $image instanceof UploadedFile ? $image : null,
        );
    }

    public function wantsPublication(): bool
    {
        return self::ACTION_PUBLISH === $this->action;
    }

    /**
     * @return list<mixed>
     */
    private static function decodeJsonList(mixed $json): array
    {
        if (!is_string($json) || '' === $json) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }
}
