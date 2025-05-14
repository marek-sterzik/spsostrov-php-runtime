<?php

namespace App\Utility;

class NameToId
{
    public static function get(string $name): string
    {
        $name = mb_strtolower(trim($name), 'utf-8');
        $name = preg_replace('/\s+/', '_', $name);
        return $name;
    }
}
