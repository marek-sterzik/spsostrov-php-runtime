<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Assignment;
use App\Form\AssignmentEditType;
use App\Utility\RoleComparator;
use App\Assignment\AssignmentActions;
use App\Component\Action;
use App\Markdown\Markdown;
use App\Component\StudentClassPattern as StudentClassPatternComponent;

class AssignmentDetailController extends AbstractController
{
    public function __construct(private AssignmentActions $assignmentActions, private Markdown $markdown)
    {
    }

    #[IsGranted('ROLE_TEACHER')]
    #[Route("/assignment/{assignment}", name: "assignment-detail")]
    public function index(Assignment $assignment): Response
    {
        $me = $this->getUserEntity();
        $actions = array_filter(
            $this->assignmentActions->generate($assignment, false),
            fn ($item) => ($item !== null)
        );
        $back = Action::get($this->getBackUrl(true))
            ->label("zpět")->cssClass("btn-secondary")->icon("bi-arrow-left")
        ;

        $isMe = ($assignment->getOwner() === $me) ? true : false;
        return $this->render("assignment-detail.html.twig", [
            "backAction" => $back,
            "assignment" => $assignment,
            "actions" => $actions,
            "ownerIsMe" => $isMe,
            "studentClassPattern" => StudentClassPatternComponent::get($assignment->getClasses()),
            "descriptionHtml" => $this->markdown->getDescriptionHtml($assignment, "h2"),
        ]);
    }

    protected function getDefaultBackUrl(): string
    {
        return $this->generateUrl("assignments");
    }
}
