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
    case Draft = "draft";

    #[Description("připraveno k aktivaci")]
    #[Parameter("type", "primary")]
    case Ready = "ready";

    #[Description("aktivováno")]
    #[Parameter("type", "danger")]
    case Active = "active";

    #[Description("odevzdávání ukončeno")]
    #[Parameter("type", "warning")]
    case Finished = "finished";

    #[Description("uzavřeno")]
    #[Parameter("type", "secondary")]
    case Closed = "closed";

    public function canTransitTo(self $state): bool
    {
        if ($this === $state) {
            return false;
        }
        return match ($this) {
            self::Draft => ($state === self::Ready || $state === self::Active),
            self::Ready => ($state === self::Active),
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
