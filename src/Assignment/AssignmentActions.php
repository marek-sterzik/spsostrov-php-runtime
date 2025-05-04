<?php

namespace App\Assignment;

use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Assignment;
use App\Entity\User;
use App\Component\Action;
use App\Framework\Router;

class AssignmentActions
{
    public function __construct(private Router $router, private Security $security)
    {
    }

    private function getUser(): ?User
    {
        $user = $this->security->getUser()?->getUserData();
        assert($user === null || $user instanceof User);
        return $user;
    }

    public function generate(Assignment $assignment, bool $forList = false): array
    {
        $actions = [];

        $user = $this->getUser();

        if ($forList) {
            $actions[] = Action::get(
                $this->router->generate("assignment-detail", ["assignment" => $assignment->getId(), "_back" => true]),
            )->label("detail")->cssClass("btn-primary")->icon("bi-eye");
        }

        if ($assignment->canBeEditedBy($user)) {
            $actions[] = Action::get(
                $this->router->generate("assignment", ["assignment" => $assignment->getId(), "_back" => true]),
            )->label("upravit")->cssClass("btn-primary")->icon('bi-pencil-square');
        }
        if ($assignment->canBeDeletedBy($user)) {
            $actions[] = null;
            $actions[] = Action::get(
                $this->router->generate("assignment-delete", ["assignment" => $assignment->getId(), "_back" => $forList]),
            )->label("smazat")->cssClass("btn-danger")->icon('bi-trash')->confirm(...$this->confirmDeleteMessage($assignment));
        }
        return $actions;
    }

    private function confirmDeleteMessage(Assignment $assignment): array
    {
        $message = sprintf("Chcete opravdu smazat zadání \"%s\"", $assignment->getCaption());
        $title = "potvrdit smazání";
        return [$message, $title];
    }
}
