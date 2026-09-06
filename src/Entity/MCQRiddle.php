<?php

namespace App\Entity;

use App\Repository\MCQRiddleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: MCQRiddleRepository::class)]
class MCQRiddle extends Riddle
{
    /**
     * @var string[]
     */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 2, minMessage: 'Un QCM doit proposer au moins {{ limit }} choix.')]
    private array $choices = [];

    /**
     * @var string[]
     */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'Un QCM doit avoir au moins {{ limit }} bonne réponse.')]
    private array $answers = [];

    /**
     * Le concepteur décide si le nombre de bonnes réponses est annoncé au joueur.
     * Le révéler facilite la lecture de la question mais réduit l'espace de recherche.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $revealAnswerCount = true;

    public function getType(): string
    {
        return 'mcq';
    }

    public function getExpectedAnswerCount(): ?int
    {
        return $this->revealAnswerCount ? count($this->answers) : null;
    }

    /**
     * @return string[]
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    /**
     * @param string[] $choices
     *
     * @return $this
     */
    public function setChoices(array $choices): static
    {
        $this->choices = $choices;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /**
     * @param string[] $answers
     *
     * @return $this
     */
    public function setAnswers(array $answers): static
    {
        $this->answers = $answers;

        return $this;
    }

    #[Groups(['riddle:solution'])]
    public function isRevealAnswerCount(): bool
    {
        return $this->revealAnswerCount;
    }

    public function setRevealAnswerCount(bool $revealAnswerCount): static
    {
        $this->revealAnswerCount = $revealAnswerCount;

        return $this;
    }

    /**
     * Une bonne réponse absente des choix rend l'énigme injouable : le joueur ne peut
     * pas la sélectionner, et la comparaison ensembliste ne peut jamais aboutir.
     */
    #[Assert\Callback]
    public function validateAnswersAreOfferedChoices(ExecutionContextInterface $context): void
    {
        foreach (array_diff($this->answers, $this->choices) as $orphan) {
            $context->buildViolation('La bonne réponse "{{ answer }}" ne figure pas parmi les choix proposés.')
                ->setParameter('{{ answer }}', $orphan)
                ->atPath('answers')
                ->addViolation();
        }
    }
}
