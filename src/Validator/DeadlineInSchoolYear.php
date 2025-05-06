<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "CLASS", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class DeadlineInSchoolYear extends Constraint
{
    public function __construct(
        public string $message = 'Datum musí být nastaven v rámci nastaveného školního roku.',
        public string $deadlineWrongOrderMessage = 'Nepřekročitelný termín odevzdání nesmí být nastaven před termínem odevzdání.'
    ) {
        parent::__construct();
    }

    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
