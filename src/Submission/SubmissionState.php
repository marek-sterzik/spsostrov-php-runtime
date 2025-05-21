<?php

namespace App\Submission;

use App\Enum\Description;
use App\Enum\Parameter;
use App\Enum\EnumTrait;

enum SubmissionState: string
{
    use EnumTrait;

    #[Description("rozpracované")]
    #[Parameter("icon", "bi-pencil")]
    case Draft = "draft";

    #[Description("zahozeno")]
    #[Parameter("icon", "bi-trash")]
    case Trash = "trash";
    
    #[Description("zpracovává se")]
    #[Parameter("icon", "bi-arrow-repeat")]
    case Submitted = "submitted";
    
    #[Description("synchronizuje se")]
    #[Parameter("icon", "bi-arrow-down-up")]
    case Packed = "packed";
    
    #[Description("hotovo")]
    #[Parameter("icon", "bi-file-earmark-check")]
    case Synced = "synced";
    
    #[Description("hotovo")]
    #[Parameter("icon", "bi-file-earmark")]
    case NotSynced = "not_synced";

    public function isClosed(): bool
    {
        return match ($this) {
            self::Draft => false,
            default => true
        };
    }

    public function isSynced(): bool
    {
        return match ($this) {
            self::Synced => true,
            default => false
        };
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::Synced => true,
            self::NotSynced => true,
            default => false
        };
    }

    public function isWaiting(): bool
    {
        if ($this->isFinal()) {
            return false;
        }
        if ($this === self::Draft) {
            return false;
        }
        return true;
    }
}
