<?php

namespace App\Validator;

use Exception;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Entity\Assignment;
use App\Utility\CurrentSchoolYear;

class DeadlineInSchoolYearValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var DeadlineInSchoolYear $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if (!($value instanceof Assignment)) {
            throw new Exception("This constraint is allowed to be set only on objects of type " . Assignment::class);
        }

        $schoolYear = $value->getSchoolYear();
        $validationData = [
            "softDeadline" => $value->getSoftDeadline(),
            "hardDeadline" => $value->getHardDeadline(),
        ];

        foreach ($validationData as $path => $date) {
            if ($date === null) {
                continue;
            }
            if ($schoolYear !== CurrentSchoolYear::get($date)) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->atPath($path)
                    ->addViolation();
            }
        }

        if ($validationData['softDeadline'] !== null &&
            $validationData['hardDeadline'] !== null &&
            $validationData['hardDeadline'] < $validationData['softDeadline']
        ) {
            $this->context
                ->buildViolation($constraint->deadlineWrongOrderMessage)
                ->atPath("hardDeadline")
                ->addViolation();
        }
    }
}
