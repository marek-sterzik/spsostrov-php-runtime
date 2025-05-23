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

    #[Description("uzamknuto")]
    #[Parameter("icon", "bi-lock")]
    case Locked = "locked";

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

    public static function drafts(): array
    {
        return [self::Draft, self::Locked];
    }

    public static function draftsAndTrash(): array
    {
        return [self::Draft, self::Locked, self::Trash];
    }

    public function isDraft(): bool
    {
        return match ($this) {
            self::Draft => true,
            self::Locked => true,
            default => false,
        };
    }

    public function isWritableDraft(): bool
    {
        return match ($this) {
            self::Draft => true,
            default => false,
        };
    }

    public function isLockedDraft(): bool
    {
        return match ($this) {
            self::Locked => true,
            default => false,
        };
    }

    public function isClosed(): bool
    {
        return match ($this) {
            self::Draft => false,
            self::Locked => false,
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
        if ($this === self::Draft || $this === self::Locked) {
            return false;
        }
        return true;
    }
}
