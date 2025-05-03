<?php

namespace App\Component;

class Cell
{
    public static function cell(mixed $cell): self
    {
        if ($cell instanceof self) {
            return $cell;
        }
        if (is_array($cell)) {
            $cellHtml = "";
            foreach ($cell as $item) {
                if (is_string($item)) {
                    $cellHtml .= $item;
                } elseif (($item instanceof self) || ($item instanceof Action) || ($item instanceof MultiAction)) {
                    $cellHtml .= $item->render();
                } else {
                    $cellHtml .= (string)$item;
                }
            }
            return self::html($cellHtml);
        }
        return self::text($cell);
    }

    public static function text(?string $text): self
    {
        return new self(htmlspecialchars($text ?? ''), [], null);
    }

    public static function html(?string $html): self
    {
        return new self($html ?? '', [], null);
    }

    private function __construct(private string $html, private array $attributes, private ?string $tag)
    {
    }

    public function attribute(string $attribute, string $value): self
    {
        return $this->attributes([$attribute => $value]);
    }

    public function attributes(array $attributes): self
    {
        return new self($this->html, array_merge($this->attributes, $attributes), $this->tag);
    }

    public function tag(string $tag): self
    {
        return new self($this->html, $this->attributes, $tag);
    }

    public function render(string $defaultTag = "td"): string
    {
        $tag = $this->tag ?? $defaultTag;
        $attributes = "";
        foreach ($this->attributes as $attr => $value) {
            $attributes .= sprintf(" %s=\"%s\"", $attr, htmlspecialchars($value));
        }
        return sprintf("<%s%s>%s</%s>", $tag, $attributes, $this->html, $tag);
    }
}
