<?php

namespace App\StudentClass;

use Exception;

class StudentClass
{
    const PATTERNS = [
        'single_class' => '/^([A-Z]+[0-9]*[A-Z]*)$/',
        'everything' => '/^\*$/',
        'num_suffix' => '/^([A-Z]+)\*$/',
        'suffix' => '/^([A-Z]+[0-9]+)\*$/',
        'prefix_num' => '/^\*([A-Z]+)$/',
        'num' => '/^([A-Z]+)\*([A-Z]+)$/',
        'prefix_suffix' => '/^\*([0-9]+)\*$/',
        'prefix' => '/^\*([0-9]+[A-Z]*)$/',
    ];
    public function normalizeStudentClass(string $studentClass): ?string
    {
        $studentClass = preg_replace('/\s+/', '', $studentClass);
        if ($studentClass === '') {
            return null;
        }
        $studentClass = strtoupper($studentClass);

        if (!preg_match(self::PATTERNS["single_class"], $studentClass)) {
            return null;
        }
        return $studentClass;
    }

    public function normalizeStudentClassPattern(string $studentClassPattern): ?string
    {
        $parsed = $this->parsePattern($studentClassPattern);
        return $this->parsedToNormalized($parsed);
    }

    public function studentClassPatternToRegexp(string $studentClassPattern): ?string
    {
        $parsed = $this->parsePattern($studentClassPattern);
        return $this->parsedToRegexp($parsed);
    }

    public function parseStudentClassPattern(string $studentClassPattern): array
    {
        $parsed = $this->parsePattern($studentClassPattern);
        return [
            "normalized" => $this->parsedToNormalized($parsed),
            "regexp" => $this->parsedToRegexp($parsed),
        ];
    }

    private function parsedToNormalized(?array $parsed): ?string
    {
        if ($parsed === null) {
            return null;
        }
        return implode(", ", $parsed);
    }

    private function parsedToRegexp(?array $parsed): ?string
    {
        if ($parsed === null) {
            return null;
        }
        $parsed = array_map(fn ($item) => $this->studentClassSinglePatternToRegexp($item), $parsed);
        return '^' . implode("|", $parsed) . "$";
    }


    private function parsePattern(string $studentClassPattern): ?array
    {
        $studentClassPattern = trim($studentClassPattern);
        if ($studentClassPattern === '') {
            return null;
        }

        $studentClassPattern = strtoupper($studentClassPattern);
        $parsed = preg_split('/\s*,\s*/', $studentClassPattern);
        $parsed = array_filter($parsed, fn ($item) => ($item !== ""));
        foreach ($parsed as &$studentClassSinglePattern) {
            $studentClassSinglePattern = $this->normalizeStudentClassSinglePattern($studentClassSinglePattern);
            if ($studentClassSinglePattern === null) {
                return null;
            }
        }
        return $parsed;
    }

    private function studentClassSinglePatternToRegexp(string $studentClassSinglePattern): string
    {
        $patternType = null;
        $matches = null;
        foreach (self::PATTERNS as $type => $pattern) {
            if (preg_match($pattern, $studentClassSinglePattern, $matches)) {
                $patternType = $type;
                break;
            }
        }
        if ($patternType === null) {
            throw new Exception("Bug occured");
        }

        switch ($patternType) {
            case 'single_class':
                return $matches[1];
            case 'everything':
                return '[A-Z]+[0-9]*[A-Z]*';
            case 'num_suffix':
                return $matches[1] . '[0-9]*[A-Z]*';
            case 'suffix':
                return $matches[1] . '[A-Z]*';
            case 'prefix_num':
                return '[A-Z]+[0-9]*' . $matches[1];
            case 'num':
                return $matches[1] . '[0-9]+' . $matches[2];
            case 'prefix_suffix':
                return '[A-Z]+' . $matches[1] . '[A-Z]*';
            case 'prefix':
                return '[A-Z]+' . $matches[1];
            default:
                throw new Exception("Bug occured");
        }
    }

    private function normalizeStudentClassSinglePattern(string $studentClassSinglePattern): ?string
    {
        $studentClassSinglePattern = preg_replace('/\s+/', '', $studentClassSinglePattern);
        $studentClassSinglePattern = preg_replace('/\*+/', '*', $studentClassSinglePattern);
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $studentClassSinglePattern)) {
                return $studentClassSinglePattern;
            }
        }
        return null;
    }
}
