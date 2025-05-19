<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Assignment;
use App\Form\AssignmentEditType;
use App\Utility\RoleComparator;
use App\Assignment\AssignmentActions;
use App\Assignment\AssignmentLink;
use App\Component\Action;
use App\Markdown\Markdown;
use App\Repository\SubmissionRepository;
use App\Component\StudentClassPattern as StudentClassPatternComponent;

class AssignmentDetailController extends AbstractController
{
    public function __construct(
        private AssignmentActions $assignmentActions,
        private Markdown $markdown,
        private SubmissionRepository $submissionRepository,
        private AssignmentLink $assignmentLink
    ) {
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

        $isMe = ($assignment->getOwner() === $me) ? true : false;

        $submissionCount = $this->submissionRepository->countSubmissions($assignment);
        $submissionsLink = null;
        if ($submissionCount !== null && $submissionCount !== 0) {
            $submissionsLink = $this->assignmentLink->submissions($assignment, true);
        }
        return $this->render("assignment-detail.html.twig", [
            "assignment" => $assignment,
            "actions" => $actions,
            "ownerIsMe" => $isMe,
            "studentClassPattern" => StudentClassPatternComponent::get($assignment->getClasses()),
            "descriptionHtml" => $this->markdown->getDescriptionHtml($assignment, "h4"),
            "submissionCount" => $submissionCount,
            "submissionsLink" => $submissionsLink,
        ]);
    }

    protected function backLinkEnabled(): bool
    {
        return true;
    }

    protected function getDefaultBackUrl(): string
    {
        return $this->generateUrl("assignments");
    }
}
