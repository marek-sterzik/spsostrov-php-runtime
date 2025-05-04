<?php

namespace App\Assignment;

use App\Enum\Description;
use App\Enum\EnumTrait;

enum AssignmentState: string
{
    use EnumTrait;

    #[Description("rozpracované")]
    case Draft = "draft";

    case Submitting = "submitting";
}
