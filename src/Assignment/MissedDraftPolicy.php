<?php

namespace App\Assignment;

use App\Enum\Description;
use App\Enum\Parameter;
use App\Enum\EnumTrait;

enum MissedDraftPolicy: string
{
    use EnumTrait;

    #[Description("přijmout pokud je odevzdání první (doporučeno)")]
    case AcceptFirst = "accept_first";

    #[Description("vždy zahodit")]
    case Dismiss = "dismiss";

    #[Description("vždy přijmout")]
    case AcceptAlways = "accept_always";

    public static function showDraftsInAdminPolicies(): array
    {
        $policies = [];
        foreach (self::cases() as $policy) {
            if ($policy->showDraftInAdmin()) {
                $policies[] = $policy;
            }
        }
        return $policies;
    }

    public function showDraftInAdmin(): bool
    {
        return match ($this) {
            self::Dismiss => false,
            default => true
        };
    }

    public function allowReactivation(): bool
    {
        return match ($this) {
            self::Dismiss => true,
            default => false
        };
    }

    public function delayedDeletion(): bool
    {
        return $this->allowReactivation();
    }
}
