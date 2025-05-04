<?php

namespace App\Enum;
 
use Attribute;
 
#[Attribute]
class Description
{
    public function __construct(private string $description)
    {
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getParamName(): string
    {
        return "description";
    }

    public function getKey(): string
    {
        return "d";
    }

    public function getValue(): mixed
    {
        return $this->getDescription();
    }
}
