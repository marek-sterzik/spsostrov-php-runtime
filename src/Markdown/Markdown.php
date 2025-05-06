<?php

namespace App\Markdown;

use Michelf\MarkdownExtra;
use App\Entity\Assignment;
use Exception;

class Markdown
{
    public function invalidate(Assignment $assignment): self
    {
        return $this;
    }

    public function refresh(Assignment $assignment): self
    {
        return $this;
    }

    public function getDescriptionHtml(Assignment $assignment, string $mainHeading = 'h2'): ?string
    {
        $html = $this->getDescriptionHtmlRaw($assignment);
        if ($html !== null) {
            $html = $this->patchHeading($html, $mainHeading);
        }
        return $html;
    }

    private function getDescriptionHtmlRaw(Assignment $assignment): ?string
    {
        $description = $assignment->getDescription();
        if ($description === null) {
            return null;
        }

        return MarkdownExtra::defaultTransform($description);
    }

    private function patchHeading(string $html, string $mainHeading): string
    {
        $shift = $this->getHeadingShift($mainHeading);
        if ($shift > 0) {
            $html = preg_replace_callback('/\<\s*h([1-6])([ \>])/', function ($matches) use ($shift) {
                $n = min(((int)$matches[1]) + $shift, 6);
                return sprintf("<h%d%s", $n, $matches[2]);
            }, $html);
        }
        return $html;
    }

    private function getHeadingShift(string $mainHeading): int
    {
        if (!preg_match('/^[hH]([0-6])$/', $mainHeading, $matches)) {
            throw new Exception(sprintf("invalid heading tag: %s", $mainHeading));
        }
        return ((int)$matches[1]) - 1;
    }
}
