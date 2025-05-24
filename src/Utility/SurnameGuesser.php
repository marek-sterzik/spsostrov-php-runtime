<?php

namespace App\Utility;

class SurnameGuesser
{
    public function guessSurname(string $name): string
    {
        $name = trim($name);
        if ($name === "") {
            return "";
        }
        $parsed = preg_split('/\s+/', $name);
        return array_pop($parsed);
    }
}
