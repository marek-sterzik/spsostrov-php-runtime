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
    
    #[Description("odevzdáno")]
    case Submitted = "submitted";
    
    #[Description("zkomprimováno")]
    case Packed = "packed";
    
    #[Description("synchronizováno")]
    case Synced = "synced";
}
