<?php

namespace App\Component;

use Exception;

class MultiAction implements Component
{
    public static function get(...$actions): self
    {
        return new self($actions, null);
    }

    private function __construct(private array $actions, private ?string $cssClass)
    {
        foreach ($actions as $action) {
            if ($action !== null && !($action instanceof Action)) {
                throw new Exception("Multiaction must consist of instances of " . Action::class);
            }
        }
    }

    public function action(Action $action): self
    {
        return new self(array_merge($this->actions, [$action]), $this->cssClass);
    }

    public function separator(): self
    {
        return new self(array_merge($this->actions, [null]), $this->cssClass);
    }

    public function cssClass(?string $cssClass): self
    {
        return new self($this->actions, $cssClass);
    }


    public function render(): string
    {
        $actions = $this->actions;
        if (empty($actions)) {
            return "";
        }
        $primaryAction = array_shift($actions);
        if (empty($actions)) {
            return $primaryAction->render();
        }
        $cssClass = isset($this->cssClass) ? (" " . $this->cssClass) : "";
        $html = "";
        $html .= "<div class=\"btn-group\">";
        $html .= $primaryAction->cssClass($this->cssClass)->render();
        $html .= sprintf("<button type=\"button\" class=\"btn btn-sm dropdown-toggle dropdown-toggle-split%s\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">", $cssClass);
        $html .= "<span class=\"visually-hidden\">Toggle Dropdown</span>";
        $html .= "</button>";
        $html .= "<ul class=\"dropdown-menu\">";
        foreach ($actions as $action) {
            if ($action !== null) {
                $html .= "<li>" . $action->renderAsDropdown() . "</li>";
            } else {
                $html .= "<li><hr class=\"dropdown-divider\"></li>";
            }
        }
        $html .= "</ul>";
        $html .= "</div>";
        return $html;
    }
}
