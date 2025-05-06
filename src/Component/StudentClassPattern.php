<?php

namespace App\Component;

class StudentClassPattern implements Component
{
    public static function get(string $pattern): self
    {
        return new self($pattern);
    }

    private function __construct(private string $pattern)
    {
    }

    public function render(): string
    {
        $html = "<span class=\"student-class-pattern\">";
        $parts = preg_split('/([,\s]+)/', $this->pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if ($i%2 == 0) {
                if ($part !== '') {
                    $html .= "<span class=\"sg\">";
                    $partParts = preg_split('/(\*)/', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
                    foreach ($partParts as $partPart) {
                        if ($partPart === '*') {
                            $html .= "<span class=\"wc\">*</span>";
                        } else {
                            $html .= htmlspecialchars($partPart);
                        }
                    }
                    $html .= "</span>";
                }
            } else {
                $html .= htmlspecialchars($part);
            }
        }
        $html .= "</span>";
        return $html;
    }
}
