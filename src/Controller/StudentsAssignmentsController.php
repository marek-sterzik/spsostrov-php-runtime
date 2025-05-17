<?php

namespace App\Controller;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Assignment;
use App\Entity\Submission;
use App\Component\Cell;
use App\Component\Action;
use App\SearchTool\SearchTool;
use App\SearchTool\Builder;
use App\Form\Filter\StudentAssignmentsType;
use App\Assignment\AssignmentState;
use App\Utility\CurrentSchoolYear;
use App\Markdown\Markdown;
use App\Repository\SubmissionRepository;
use App\Submission\SubmissionState;

class StudentsAssignmentsController extends AbstractDbTableController
{
    public function __construct(
        private Markdown $markdown,
        private SubmissionRepository $submissionRepository
    ) {
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submit", name: "submit")]
    public function index(): Response
    {
        $this->getEntityManager()->getRepository(Assignment::class)->updateStates();
        $this->enableModule("students-assignments");
        return $this->renderTable();
    }

    protected function getBaseQueryBuilder(array $filterData): QueryBuilder
    {
        $me = $this->getUserEntity();
        $qb = $this->getEntityManager()->getRepository(Assignment::class)->createQueryBuilder('a');
        $qb
            ->andWhere("a.state = :activeState")
            ->setParameter(":activeState", AssignmentState::Active)
            ->andWhere("a.schoolYear = :schoolYear")
            ->setParameter(":schoolYear", CurrentSchoolYear::get())
            ->andWhere('REGEXP(:myClass, a.classesRegexp) = true')
            ->setParameter(":myClass", $me->getRealStudentClass())
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull("a.hardDeadline"),
                $qb->expr()->gte("a.hardDeadline", ":now")
            ))
            ->setParameter(":now", new DateTimeImmutable())
        ;
        $searchTool = new SearchTool();
        $searchTool->handle("caption", function(Builder $builder) {
            return $builder->expr()->like("a.caption", $builder->var("%" . $builder->searchString() . "%"));
        });
        $searchTool->handle("description", function(Builder $builder) {
            return $builder->expr()->like("a.description", $builder->var("%" . $builder->searchString() . "%"));
        });
        $searchTool->search($qb, $filterData['q'] ?? '');
        $qb->addOrderBy("a.mainOrder", "DESC");
        $qb->addOrderBy("a.createdAt", "DESC");
        return $qb;
    }

    protected function getHeader(array $filterData): array
    {
        return [
            "assignment" => null,
        ];
    }

    protected function recordToArray(mixed $assignment): array
    {
        assert($assignment instanceof Assignment);
        return [
            "assignment" => $this->getAssignmentCell($assignment),
        ];
    }

    private function getAssignmentCell(Assignment $assignment): mixed
    {
        $descriptionHtml = $this->markdown->getDescriptionHtml($assignment, "h2");
        $lastSubmission = $this->submissionRepository->getLastSubmission($assignment, $this->getUserEntity());

        $submitAction = $this->getSubmitAction($assignment, $lastSubmission);

        $content = $this->renderView(
            "snippets/student-assignment.html.twig",
            [
                "assignment" => $assignment,
                "descriptionHtml" => $descriptionHtml,
                "submit" => $submitAction
            ]
        );
        return Cell::html($content);
    }

    protected function getForm(array $formData): ?FormInterface
    {
        return $this->createForm(StudentAssignmentsType::class, $formData);
    }

    protected function getDefaultFilterData(): array
    {
        return [];
    }

    private function getSubmitAction(Assignment $assignment, ?Submission $lastSubmission): Action
    {
        if ($lastSubmission === null ||
            $lastSubmission->getState() === SubmissionState::Draft ||
            $assignment->getSubmissionMode()->allowMultiple()
        ) {
            $label = $this->getSubmitLabel($lastSubmission);
            $url = $this->generateUrl("create-submission", ["assignment" => $assignment->getId(), "_back" => true]);
            $submitAction = Action::get($url)
                ->label($label)->cssClass("btn-danger")->icon("bi-rocket-takeoff");
        } else {
            $submitAction = Action::get("#")
                ->label("již odevzdáno")->cssClass("btn-danger disabled")->icon("bi-rocket-takeoff");
        }

        return $submitAction;
    }

    private function getSubmitLabel(?Submission $lastSubmission): string
    {
        if ($lastSubmission === null) {
            return "odevzdat";
        }

        if ($lastSubmission->getState() === SubmissionState::Draft) {
            return "dokončit odevzdání";
        }

        return "znovu odevzdat";
    }
}
