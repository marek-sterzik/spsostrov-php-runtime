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

    #[Description("zahozeno")]
    case Trash = "trash";
    
    #[Description("odevzdáno")]
    case Submitted = "submitted";
    
    #[Description("zkomprimováno")]
    case Packed = "packed";
    
    #[Description("synchronizováno")]
    case Synced = "synced";
    
    #[Description("nesynchronizováno")]
    case NotSynced = "not_synced";
    
    public function isSynced(): bool
    {
        return match ($this) {
            self::Synced => true,
            default => false
        };
    }
}
