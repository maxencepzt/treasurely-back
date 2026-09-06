<?php

namespace App\Service\Designer;

use App\Dto\Designer\RiddleInput;
use App\Entity\GPSRiddle;
use App\Entity\MCQRiddle;
use App\Entity\QRRiddle;
use App\Entity\Riddle;
use App\Entity\TextRiddle;
use App\Entity\TreasureHunt;

/**
 * Passe d'une RiddleInput à une entité. Le type d'une énigme persistée est immuable
 * (héritage à table unique) : la création choisit la classe, la mise à jour ne
 * touche qu'aux champs.
 */
final class RiddleMapper
{
    public function create(RiddleInput $input, TreasureHunt $hunt, int $orderNumber): Riddle
    {
        $riddle = match ($input->type) {
            'text' => new TextRiddle(),
            'gps' => new GPSRiddle(),
            'mcq' => new MCQRiddle(),
            'qr' => new QRRiddle(),
            default => throw new \InvalidArgumentException(sprintf('Type d\'énigme inconnu : "%s".', $input->type)),
        };
        $riddle->setHunt($hunt);
        $this->apply($riddle, $input, $orderNumber);

        return $riddle;
    }

    /**
     * Forme attendue par riddle-manager.js, identique à celle que RiddleInput relit :
     * ce qui part vers le navigateur revient tel quel, identifiant compris.
     *
     * @return array<string, mixed>
     */
    public function toArray(Riddle $riddle): array
    {
        $data = [
            'id' => $riddle->getId(),
            'type' => $riddle->getType(),
            'title' => $riddle->getTitle(),
            'description' => $riddle->getDescription(),
            'difficulty' => $riddle->getDifficulty(),
            'orderNumber' => $riddle->getOrderNumber(),
            'maxScoringAttempts' => $riddle->getMaxScoringAttempts(),
        ];

        return $data + match (true) {
            $riddle instanceof TextRiddle => ['answer' => $riddle->getAnswer()],
            $riddle instanceof QRRiddle => ['code' => $riddle->getCode()],
            $riddle instanceof GPSRiddle => ['latitude' => $riddle->getLatitude(), 'longitude' => $riddle->getLongitude()],
            $riddle instanceof MCQRiddle => [
                'choices' => $riddle->getChoices(),
                'answers' => $riddle->getAnswers(),
                'revealAnswerCount' => $riddle->isRevealAnswerCount(),
            ],
            default => [],
        };
    }

    public function apply(Riddle $riddle, RiddleInput $input, int $orderNumber): void
    {
        $riddle
            ->setTitle($input->title)
            ->setDescription($input->description)
            ->setDifficulty($input->difficulty)
            ->setMaxScoringAttempts($input->maxScoringAttempts)
            ->setOrderNumber($orderNumber);

        match (true) {
            $riddle instanceof TextRiddle => $riddle->setAnswer((string) $input->answer),
            $riddle instanceof QRRiddle => $riddle->setCode((string) $input->code),
            $riddle instanceof GPSRiddle => $riddle
                ->setLatitude($input->latitude ?? 0.0)
                ->setLongitude($input->longitude ?? 0.0),
            $riddle instanceof MCQRiddle => $riddle
                ->setChoices($input->choices)
                ->setAnswers($input->answers)
                ->setRevealAnswerCount($input->revealAnswerCount),
            default => throw new \LogicException(sprintf('Sous-type non géré : %s.', $riddle::class)),
        };
    }
}
