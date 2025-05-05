<?php

namespace App\Assignment;

use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Assignment;
use App\Entity\User;
use App\Component\Action;
use App\Framework\Router;
use SPSOstrov\SSOBundle\SSOUser;

class AssignmentActions
{
    public function __construct(private Router $router, private Security $security)
    {
    }

    private function getUser(): ?User
    {
        $user = $this->security->getUser();
        assert($user === null || $user instanceof SSOUser);
        $user = $user?->getUserData();
        assert($user === null || $user instanceof User);
        return $user;
    }

    public function generate(Assignment $assignment, bool $forList = false): array
    {
        $actions = [];

        $user = $this->getUser();

        if ($forList) {
            $actions[] = Action::get(
                $this->router->generate(
                    "assignment-detail",
                    ["assignment" => $assignment->getId(), "_back" => true]
                )
            )->label("detail")->cssClass("btn-primary")->icon("bi-eye");
        }

        if ($assignment->canBeEditedBy($user)) {
            $actions[] = Action::get(
                $this->router->generate("assignment", ["assignment" => $assignment->getId(), "_back" => true]),
            )->label("upravit")->cssClass("btn-primary")->icon('bi-pencil-square');
        }
        $allowReadyTransition = $assignment->canTransitTo($user, AssignmentState::Ready);
        $allowActiveTransition = $assignment->canTransitTo($user, AssignmentState::Active);
        if ($allowReadyTransition && $allowActiveTransition) {
            $actions[] = Action::get(
                $this->getTransitionAction($assignment, AssignmentState::Ready),
            )->label("aktivovat")->cssClass("btn-success")->icon("bi-check-square")
                ->confirm(...$this->confirmCloseActivateAction($assignment, $allowActiveTransition))
                ->confirmButtons("uzavřít a připravit k aktivaci", null, "uzavřít a aktivovat")
                ->thirdAction($this->getTransitionAction($assignment, AssignmentState::Active), "danger");
        } elseif ($allowReadyTransition) {
            $actions[] = Action::get(
                $this->getTransitionAction($assignment, AssignmentState::Ready),
            )->label("připravit k aktivaci")->cssClass("btn-success")->icon("bi-check-square")
                ->confirm(...$this->confirmCloseActivateAction($assignment, $allowActiveTransition))
                ->confirmButtons("uzavřít a připravit k aktivaci");
        } elseif ($allowActiveTransition) {
            $actions[] = Action::get(
                $this->getTransitionAction($assignment, AssignmentState::Active),
            )->label("aktivovat")->cssClass("btn-success")->icon("bi-check-square")
                ->confirm(...$this->confirmCloseActivateAction($assignment, $allowActiveTransition))
                ->confirmButtons("aktivovat")->confirmType("danger");
        }
        if ($assignment->canBeDeletedBy($user)) {
            $actions[] = null;
            $actions[] = Action::get(
                $this->router->generate(
                    "assignment-delete",
                    ["assignment" => $assignment->getId(), "_back" => $forList]
                )
            )->label("smazat")->cssClass("btn-danger")->icon('bi-trash')
                ->confirm(...$this->confirmDeleteMessage($assignment))->confirmButtons("opravdu smazat");
        }
        return $actions;
    }

    private function getTransitionAction(Assignment $assignment, AssignmentState $state): string
    {
        return $this->router->generate(
            "assignment-set-state",
            ["assignment" => $assignment->getId(), "state" => $state->value]
        );
    }

    private function confirmDeleteMessage(Assignment $assignment): array
    {
        $message = sprintf("Chcete opravdu smazat zadání \"%s\"?", $assignment->getCaption());
        $title = "potvrdit smazání";
        return [$message, $title];
    }

    private function confirmCloseActivateAction(Assignment $assignment, bool $activationAvailable): array
    {
        if ($assignment->getState() === AssignmentState::Draft) {
            $message = "Po uzavření už nebude možné zadání měnit.\n" .
                ($activationAvailable ? "Po aktivaci bude zadání viditelné studentům.\n" : "") .
                "Co si přejete udělat?";
            $title = "potvrdit uzavření";
        } else {
            $message = "Po aktivaci bude zadání viditelné studentům.\n" .
                "Chcete skutečně pokračovat?";
            $title = "potvrdit aktivaci";
        }
        return [$message, $title];
    }
}
