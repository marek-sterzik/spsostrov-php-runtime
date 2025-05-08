<?php

namespace App\Assignment;

use App\Enum\Description;
use App\Enum\Parameter;
use App\Enum\EnumTrait;

enum SubmissionMode: string
{
    use EnumTrait;

    case Once = "once";

    case MultipleTimes = "multiple";
}

