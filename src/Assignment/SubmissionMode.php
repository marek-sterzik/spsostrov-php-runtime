<?php

namespace App\Assignment;

use App\Enum\Description;
use App\Enum\Parameter;
use App\Enum\EnumTrait;

enum SubmissionMode: string
{
    use EnumTrait;

    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getDescription()] = $case;
        }
        return $choices;
    }

    #[Description("více možných odevzdání, poslední platí")]
    case MultipleTimes = "multiple";
    
    #[Description("odevzdat napoprvé, bez možnosti opravy")]
    case Once = "once";

    public function allowMultiple(): bool
    {
        return match ($this) {
            self::MultipleTimes => true,
            default => false
        };
    }
}
