<?php

namespace App\Component;

class MultiAction implements Component
{
    public static function get(): self
    {
        return new self();
    }

    private function __construct()
    {
    }


    public function render(): string
    {
        $html = "";
        $html .= "<div class=\"dropdown show\">";
        $html .= "<a class=\"btn btn-secondary dropdown-toggle\" href=\"#\" role=\"button\" id=\"dropdownMenuLink\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">";
        $html .= " Dropdown link";
        $html .= "</a>";

        $html .= "<div class=\"dropdown-menu\" aria-labelledby=\"dropdownMenuLink\">";
        $html .= "<a class=\"dropdown-item\" href=\"#\">Action</a>";
        $html .= "<a class=\"dropdown-item\" href=\"#\">Another action</a>";
        $html .= "<a class=\"dropdown-item\" href=\"#\">Something else here</a>";
        $html .= "</div>";
        $html .= "</div>";
        return $html;
    }
}
