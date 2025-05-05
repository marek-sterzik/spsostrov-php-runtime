<?php

namespace App\StudentClass;

class StudentClass
{
    public function normalizeStudentClass(string $studentClass): ?string
    {
        $studentClass = preg_replace('/\s+/', '', $studentClass);
        if ($studentClass === '') {
            return null;
        }
        $studentClass = strtoupper($studentClass);

        if (!preg_match('/^[A-Z]+[0-9]*[A-Z]*$/', $studentClass)) {
            return null;
        }
        return $studentClass;
    }
}
