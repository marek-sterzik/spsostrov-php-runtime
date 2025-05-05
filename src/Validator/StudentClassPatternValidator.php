<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\StudentClass\StudentClass as StudentClassUtil;

class StudentClassPatternValidator extends ConstraintValidator
{
    public function __construct(private StudentClassUtil $studentClass)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /** @var StudentClassPattern $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value) || $this->studentClass->normalizeStudentClassPattern($value) === null) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
