<?php

namespace App\Validator;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueUserNicknameValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Security $security,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueUserNickname) {
            throw new UnexpectedTypeException($constraint, UniqueUserNickname::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $currentUser = $this->security->getUser();
        if ($currentUser instanceof User) {
            $existingUser = $this->userRepository->findOneBy(['nickname' => $value]);

            if ($existingUser && $existingUser->getId() !== $currentUser->getId()) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();
            }
        }
    }
}
