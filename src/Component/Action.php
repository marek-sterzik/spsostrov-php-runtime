<?php

namespace App\Component;

class Action implements Component
{
    public static function get(
        string $uri,
        ?string $label = null,
        ?string $cssClass = null,
        ?string $icon = null
    ): self {
        return new self($uri, $label, $cssClass, $icon);
    }

    private function __construct(
        private string $uri,
        private ?string $label = null,
        private ?string $cssClass = null,
        private ?string $icon = null
    ) {
    }

    public function label(?string $label): self
    {
        return $this->modify(["label" => $label]);
    }

    public function cssClass(?string $cssClass): self
    {
        return $this->modify(["cssClass" => $cssClass]);
    }

    public function icon(?string $icon): self
    {
        return $this->modify(["icon" => $icon]);
    }

    private function modify(array $modification): self
    {
        $data = [];
        foreach ($this as $var => $value) {
            $data[$var] = array_key_exists($var, $modification) ? $modification[$var] : $value;
        }
        return new self(...$data);
    }

    public function renderAsDropdown(): string
    {
        return $this->renderAs(sprintf(
            "class=\"dropdown-item%s\"",
            isset($this->cssClass) ? (" " . $this->cssToDropdown($this->cssClass)) : ""
        ));
    }

    public function getCssForDropdown(): ?string
    {
        return $this->cssClass;
    }

    private function cssToDropdown(string $css): string
    {
        return implode(" ", array_filter(array_map(function ($item) {
            if ($item === 'btn-danger') {
                return 'text-danger';
            } elseif ($item === "" || preg_match('/^btn-/', $item)) {
                return null;
            } else {
                return $item;
            }
        }, preg_split('/\s+/', trim($css))), fn ($item) => ($item !== null)));
    }

    public function render(): string
    {
        return $this->renderAs(sprintf(
            "class=\"btn btn-sm%s\" role=\"button\"",
            isset($this->cssClass) ? (" " . $this->cssClass) : ""
        ));
    }

    private function renderAs(string $attrs): string
    {
        if ($this->icon === null || $this->icon === "") {
            $icon = "";
        } else {
            $icon = sprintf("<i class=\"bi me-1 %s\"></i>", $this->icon);
        }
        return sprintf(
            "<a href=\"%s\"%s>%s%s</a>",
            htmlspecialchars($this->uri),
            ($attrs === "") ? "" : (" " . $attrs),
            $icon,
            htmlspecialchars($this->label ?? "!")
        );
    }
}
