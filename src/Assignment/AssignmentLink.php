<?php

namespace App\Assignment;

use App\Framework\Router;
use App\Entity\Assignment;

class AssignmentLink
{
    public function __construct(private Router $router)
    {
    }

    public function submissions(Assignment $assignment, ?bool $back = null): string
    {
        $query = "assignment-id:" . $assignment->getId();
        return $this->router->generate("all-submissions", ["q" => $query, "_back" => $back]);
    }
}
