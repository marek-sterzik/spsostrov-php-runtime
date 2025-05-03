<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class StudentClassValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /** @var StudentClass $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value) || $this->normalizeStudentClass($value) === null) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    public function normalizeStudentClass(string $studentClass): ?string
    {
        $studentClass = preg_replace('/\s+/', '', $studentClass);
        if ($studentClass === '') {
            return null;
        }
        $studentClass = strtoupper($studentClass);

        if (!preg_match('/^[A-Z]+[0-9]*[A-Z]*$/', $studentClass)) {
            return null;
        }
        return $studentClass;
    }
}
