<?php

namespace App\Component;

use Exception;

class MultiAction implements Component
{
    public static function get(...$actions): self
    {
        return new self($actions);
    }

    private function __construct(private array $actions)
    {
        foreach ($actions as $action) {
            if ($action !== null && !($action instanceof Action)) {
                throw new Exception("Multiaction must consist of instances of " . Action::class);
            }
        }
    }

    public function action(Action $action): self
    {
        return new self(array_merge($this->actions, [$action]));
    }

    public function separator(): self
    {
        return new self(array_merge($this->actions, [null]));
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

        $cssClass = $primaryAction->getCssForDropdown();
        $cssClass = ($cssClass !== null) ? (" " . $cssClass) : "";

        $dropdownButtonTemplate = "<button type=\"button\" ".
            "class=\"btn btn-sm dropdown-toggle dropdown-toggle-split%s\" ".
            "data-bs-toggle=\"dropdown\" aria-expanded=\"false\">";
        
        $html = "";
        $html .= "<div class=\"btn-group\">";
        $html .= $primaryAction->render();
        $html .= sprintf($dropdownButtonTemplate, $cssClass);
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
