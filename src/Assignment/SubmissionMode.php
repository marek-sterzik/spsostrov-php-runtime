<?php

namespace App\Assignment;

use App\Enum\Description;
use App\Enum\Parameter;
use App\Enum\EnumTrait;

enum SubmissionMode: string
{
    use EnumTrait;

    #[Description("více možných odevzdání, poslední zůstává")]
    case MultipleTimes = "multiple";
    
    #[Description("více možných odevzdání, všechny zůstávají")]
    case MultipleTimesKeep = "multiple_keep";
    
    #[Description("odevzdat napoprvé, bez možnosti opravy")]
    case Once = "once";
    

    public function allowMultiple(): bool
    {
        return match ($this) {
            self::MultipleTimes => true,
            self::MultipleTimesKeep => true,
            default => false
        };
    }

    public function deleteOld(): bool
    {
        return match ($this) {
            self::MultipleTimes => true,
            self::Once => true,
            default => false
        };
    }
}
