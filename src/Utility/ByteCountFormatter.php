<?php

namespace App\Utility;

class ByteCountFormatter
{
    const UNITS = ["B", "kB", "MB", "GB", "TB", "EB"];

    public static function format(int $byteCount): string
    {
        $minus = "";
        if ($byteCount < 0) {
            $byteCount = -$byteCount;
            $minus = "-";
        }
        $unit = self::UNITS[0];
        if ($byteCount < 1000) {
            $num = (string)$byteCount;
        } else {
            $byteCount10 = intdiv($byteCount * 10, 1024);
            for ($i = 1; $i < count(self::UNITS); $i++) {
                $unit = self::UNITS[$i];
                if ($byteCount10 < 10000) {
                    break;
                }
                $byteCount10 = intdiv($byteCount10, 1024);
            }
            if ($byteCount10 < 100) {
                $num = number_format($byteCount10 / 10, 1);
            } else {
                $num = (string)intdiv($byteCount10, 10);
            }
        }

        return sprintf("%s%s%s", $minus, $num, $unit);
    }
}
