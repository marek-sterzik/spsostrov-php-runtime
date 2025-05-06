<?php

namespace App\Utility;

use DateTimeImmutable;

class CurrentSchoolYear
{
    public static function get(?DateTimeImmutable $now = null): int
    {
        if ($now === null) {
            $now = new DateTimeImmutable();
        }
        list($year, $month) = explode(" ", $now->format("Y m"));
        $year = (int)$year;
        $month = (int)$month;
        if ($month < 9) {
            $year--;
        }
        return $year;
    }
}
