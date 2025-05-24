<?php

namespace App\Utility;

class UploadLimit
{
    public static function get(): int
    {
        $limit1 = self::limitToBytes(ini_get('upload_max_filesize'));
        $limit2 = self::limitToBytes(ini_get('post_max_size'));
        return min($limit1, $limit2);
    }

    private static function limitToBytes(string|int $val): int
    {
        $val  = trim($val);

        if (is_numeric($val)) {
            return (int)$val;
        }

        $last = strtolower($val[strlen($val)-1]);
        $val  = (int)substr($val, 0, -1);

        switch ($last) {
            case 'g':
                $val *= 1024;
                /* pass */
            case 'm':
                $val *= 1024;
                /* pass */
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}
