<?php

namespace App\Controller;

use Doctrine\ORM\QueryBuilder;
use App\Entity\Submission;
use App\Component\Cell;
use App\Component\Action;
use App\Component\MultiAction;
use App\Utility\SearchTool;
use App\Submission\SubmissionState;
use App\Repository\SubmissionRepository;
use App\FileManager\FileManager;

abstract class AbstractSubmissionsController extends AbstractDbTableController
{
    public function __construct(
        private SubmissionRepository $submissionRepository,
        private FileManager $fileManager
    ) {
    }

    protected function getBaseQueryBuilder(array $filterData): QueryBuilder
    {
        $me = $this->getUser()->getUserData();
        $qb = $this->submissionRepository->createQueryBuilder('s');
        $qb
            ->addSelect("CASE WHEN s.submittedAt IS NULL THEN 1 ELSE 0 END AS HIDDEN isSubmitted")
            ->andWhere("s.state != :trash")
            ->setParameter(":trash", SubmissionState::Trash)
        ;
        return $qb;
    }

    protected function setOrderByQuery(QueryBuilder $qb): QueryBuilder
    {
        $qb
            ->addOrderBy("isSubmitted", "DESC")
            ->addOrderBy("s.submittedAt", "DESC")
            ->addOrderBy("s.createdAt", "DESC")
        ;
        return $qb;
    }

    protected function getHeader(array $filterData): array
    {
        return [
            "caption" => "název",
            "submitter" => "odevzdal",
            "state" => "stav",
            "submittedAt" => "čas odevzdání",
            "files" => "soubory"
        ];
    }

    protected function recordToArray(mixed $submission): array
    {
        assert($submission instanceof Submission);

        $isMe = ($submission->getSubmitter() === $this->getUserEntity()) ? true : false;
        $meBadge = $isMe ? (' ' . $this->renderView('snippets/me.html.twig')) : '';
        $submissionState = $this->renderView(
            'snippets/submission-state.html.twig',
            ["state" => $submission->getState()]
        );

        $detailLink = $this->generateUrl("submission-detail", ["submission" => $submission->getId(), "_back" => true]);
        $fileCount = count($this->fileManager->listFiles($submission));
        $fileCount = $this->renderView(
            'snippets/object-count.html.twig',
            ["count" => $fileCount, "link" => $detailLink]
        );
       
        $submittedAt = $this->renderView("snippets/submitted-at.html.twig", ["submission" =>  $submission]);

        return [
            "caption" => $submission->getAssignment()->getCaption(),
            "submitter" => Cell::html(htmlspecialchars($submission->getSubmitter()->getName()) . $meBadge),
            "state" => Cell::html($submissionState),
            "submittedAt" => Cell::html($submittedAt),
            "files" => Cell::html($fileCount)->attribute("class", "text-middle"),
            "_actions" => $this->getSubmissionActions($submission),
        ];
    }


    private function getSubmissionActions(Submission $submission): array
    {
        $actions = [];
        if ($submission->getState() !== SubmissionState::Draft) {
            $actions[] = Action::get(
                $this->generateUrl(
                    "submission-detail",
                    ["submission" => $submission->getId(), "_back" => true]
                )
            )->label("detail")->cssClass("btn-primary")->icon("bi-eye");
        } elseif ($this->isStudentView()) {
            $assignment = $submission->getAssignment();
            if ($assignment->canSubmit($this->getUserEntity())) {
                $url = $this->generateUrl("create-submission", ["assignment" => $assignment->getId(), "_back" => true]);
                $actions[] = Action::get($url)
                    ->label("dokončit")->cssClass("btn-danger")->icon("bi-rocket-takeoff");
            }
        }
        
        return [MultiAction::get(...$actions)];
    }

    abstract protected function isStudentView(): bool;
}
