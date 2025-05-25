<?php

namespace App\Enum;

use ReflectionClassConstant;

trait EnumTrait
{
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getDescription()] = $case;
        }
        return $choices;
    }

    public function getDescription(): string
    {
        return Enumerator::getDescription($this);
    }

    public function getParam(string $param): mixed
    {
        return Enumerator::getParam($this, $param);
    }

    public function hasParam(string $param): bool
    {
        return Enumerator::hasParam($this, $param);
    }
}
