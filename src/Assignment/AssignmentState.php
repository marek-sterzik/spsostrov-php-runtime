<?php

namespace App\Assignment;

use App\Enum\Description;
use App\Enum\Parameter;
use App\Enum\EnumTrait;

enum AssignmentState: string
{
    use EnumTrait;

    #[Description("rozpracované")]
    #[Parameter("type", "success")]
    #[Parameter("order", 5)]
    case Draft = "draft";

    #[Description("připraveno k aktivaci")]
    #[Parameter("type", "primary")]
    #[Parameter("order", 4)]
    case Ready = "ready";

    #[Description("aktivováno")]
    #[Parameter("type", "danger")]
    #[Parameter("order", 3)]
    case Active = "active";

    #[Description("odevzdávání ukončeno")]
    #[Parameter("type", "warning")]
    #[Parameter("order", 2)]
    case Finished = "finished";

    #[Description("archivováno")]
    #[Parameter("type", "secondary")]
    #[Parameter("order", 1)]
    case Archived = "archived";

    public function canTransitTo(self $state): bool
    {
        if ($this === $state) {
            return false;
        }
        return match ($this) {
            self::Draft => ($state === self::Ready || $state === self::Active),
            self::Ready => ($state === self::Active || $state === self::Draft),
            self::Active => ($state === self::Finished),
            self::Finished => ($state === self::Archived || $state === self::Active),
            default => false
        };
    }

    public function editAllowed(): bool
    {
        return match ($this) {
            self::Draft => true,
            default => false
        };
    }

    public function deleteAllowed(): bool
    {
        return match ($this) {
            self::Draft => true,
            self::Ready => true,
            default => false
        };
    }
}
