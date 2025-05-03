<?php

namespace App\Utility;

class CurrentSchoolYear
{
    public static function get(): int
    {
        list($year, $month) = explode(" ", date("Y m"));
        $year = (int)$year;
        $month = (int)$month;
        if ($month < 9) {
            $year--;
        }
        return $year;
    }
}
