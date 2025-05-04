<?php

namespace App\Enum;
 
use Attribute;
 
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS_CONSTANT)]
class Parameter
{
    public function __construct(private string $parameter, private mixed $value)
    {
    }

    public function getKey(): string
    {
        return "p." . $this->getParameter();
    }

    public function getParamName(): string
    {
        return sprintf("parameter %s", $this->getParameter());
    }

    public function getParameter(): string
    {
        return $this->parameter;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
