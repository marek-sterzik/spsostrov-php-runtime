<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Assignment;
use App\Form\AssignmentEditType;
use App\Utility\RoleComparator;
use App\Validator\StudentClassValidator;
use App\Actions\AssignmentActions;
use App\Component\Action;

class AssignmentDetailController extends AbstractController
{
    public function __construct(private AssignmentActions $assignmentActions)
    {
    }

    #[IsGranted('ROLE_TEACHER')]
    #[Route("/assignment/{assignment}", name: "assignment-detail")]
    public function index(Assignment $assignment): Response
    {
        $actions = array_filter(
            $this->assignmentActions->generate($assignment, false),
            fn ($item) => ($item !== null)
        );
        $actions[] = Action::get($this->getBackUrl(true))->label("zpět")->cssClass("btn-secondary")->icon("bi-arrow-left");
        return $this->render("assignment-detail.html.twig", [
            "assignment" => $assignment,
            "actions" => $actions,
        ]);
    }

    protected function getDefaultBackUrl(): string
    {
        return $this->generateUrl("assignments");
    }
}
