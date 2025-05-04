<?php

namespace App\Component;

class Action implements Component
{
    public static function get(
        string $uri,
        ?string $label = null,
        ?string $cssClass = null,
        ?string $icon = null,
        array $attrs = []
    ): self {
        return new self($uri, $label, $cssClass, $icon, $attrs);
    }

    private function __construct(
        private string $uri,
        private ?string $label = null,
        private ?string $cssClass = null,
        private ?string $icon = null,
        private array $attrs = []
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

    public function attr(string $attr, ?string $value): self
    {
        return $this->attrs([$attr => $value]);
    }

    public function attrs(array $attrs): self
    {
        $newAttrs = $this->attrs;
        foreach ($attrs as $attr => $value) {
            if ($value !== null) {
                $newAttrs[$attr] = $value;
            } else {
                unset($newAttrs[$attr]);
            }
        }
        return $this->modify(["attrs" => $newAttrs]);
    }

    public function confirm(?string $message, ?string $title = null): self
    {
        return $this->attrs([
            "data-confirm-message" => $message,
            "data-confirm-title" => $title
        ]);
    }

    public function confirmButtons(
        ?string $confirmLabel = null,
        ?string $cancelLabel = null,
        ?string $thirdLabel = null
    ): self {
        return $this->attrs([
            "data-confirm-confirm-label" => $confirmLabel,
            "data-confirm-cancel-label" => $cancelLabel,
            "data-confirm-third-label" => $thirdLabel,
        ]);
    }

    public function thirdAction(?string $action, ?string $type = null): self
    {
        return $this->attrs([
            "data-confirm-third-action" => $action,
            "data-confirm-third-type" => $type,
        ]);
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
        return $this->renderAs(true);
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
            } elseif ($item === "btn") {
                return "dropdown-item";
            } elseif ($item === "" || preg_match('/^btn-/', $item)) {
                return null;
            } else {
                return $item;
            }
        }, preg_split('/\s+/', trim($css))), fn ($item) => ($item !== null)));
    }

    public function render(): string
    {
        return $this->renderAs(false);
    }

    private function getAttrs(bool $dropdown): array
    {
        $attrs = $this->attrs;
        $cssClass = $this->mergeCss("btn btn-sm", $this->cssClass);
        if (isset($attrs['class'])) {
            $attrs['class'] = $this->mergeCss($cssClass, $attrs['class']);
        } else {
            $attrs['class'] = $cssClass;
        }
        if ($attrs['class'] === null) {
            unset($attrs['class']);
        }
        if ($dropdown) {
            if (isset($attrs['class'])) {
                $attrs['class'] = $this->cssToDropdown($attrs['class']);
            }
        } else {
            $attrs['role'] = "button";
        }
        $attrs['href'] = $this->uri;
        return $attrs;
    }

    private function mergeCss(?string $css1, ?string $css2): ?string
    {
        if ($css1 !== null) {
            $css1 = trim($css1);
            if ($css1 === "") {
                $css1 = null;
            }
        }
        if ($css2 !== null) {
            $css2 = trim($css2);
            if ($css2 === "") {
                $css2 = null;
            }
        }
        if ($css2 === null || $css1 === null) {
            return $css1 ?? $css2;
        }
        return $css1 . " " . $css2;
    }

    private function renderAs(bool $dropdown): string
    {
        $attrs = $this->getAttrs($dropdown);
        $attrsString = "";
        foreach ($attrs as $attr => $value) {
            $attrsString .= sprintf(" %s=\"%s\"", $attr, htmlspecialchars($value));
        }
        if ($this->icon === null || $this->icon === "") {
            $icon = "";
        } else {
            $icon = sprintf("<i class=\"bi me-1 %s\"></i>", $this->icon);
        }
        return sprintf(
            "<a%s>%s%s</a>",
            $attrsString,
            $icon,
            htmlspecialchars($this->label ?? "!")
        );
    }
}
