<?php

namespace App\Assignment;

use App\Enum\Description;
use App\Enum\Parameter;
use App\Enum\EnumTrait;

enum MissedDraftPolicy: string
{
    use EnumTrait;

    #[Description("vždy zahodit")]
    case Dismiss = "dismiss";

    #[Description("vždy přijmout")]
    case AcceptAlways = "accept_always";

    #[Description("přijmout první odevzdání")]
    case AcceptFirst = "accept_first";
}
