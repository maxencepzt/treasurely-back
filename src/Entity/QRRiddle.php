<?php

namespace App\Entity;

use App\Dto\RiddleAttempt;
use App\Repository\QRRiddleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QRRiddleRepository::class)]
class QRRiddle extends Riddle
{
    public function getType(): string
    {
        return 'qr';
    }

    /**
     * Forme imposée du code : le préfixe identifie l'application, les chiffres l'énigme.
     * Le joueur ne saisit que les chiffres, le préfixe est affiché d'office.
     */
    public const string CODE_PREFIX = 'treasurely_';
    public const int CODE_DIGITS = 9;
    public const string CODE_PATTERN = '/^treasurely_\d{9}$/';

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: self::CODE_PATTERN, message: 'Le code doit être de la forme treasurely_ suivi de neuf chiffres.')]
    private string $code;

    /**
     * Attribué par le serveur à la création : le concepteur ne le choisit pas, il l'imprime.
     */
    public static function generateCode(): string
    {
        return self::CODE_PREFIX.str_pad((string) random_int(0, 10 ** self::CODE_DIGITS - 1), self::CODE_DIGITS, '0', \STR_PAD_LEFT);
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Le code n'étant jamais publié, le connaître vaut preuve de scan.
     */
    public function accepts(RiddleAttempt $attempt): bool
    {
        if (null === $attempt->proposal) {
            throw new \InvalidArgumentException('Le contenu du QR code est attendu.');
        }

        return trim($attempt->proposal) === $this->code;
    }
}
