<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\StudentClass\StudentClass;

class StudentClassValidator extends ConstraintValidator
{
    public function __construct(private StudentClass $studentClass)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /** @var StudentClass $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value) || $this->studentClass->normalizeStudentClass($value) === null) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
