<?php

namespace App\Submission;

use App\Enum\Description;
use App\Enum\Parameter;
use App\Enum\EnumTrait;

enum SubmissionState: string
{
    use EnumTrait;

    #[Description("rozpracované")]
    case Draft = "draft";
}
