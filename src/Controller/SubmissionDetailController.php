<?php

namespace App\Controller;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;

use App\Submission\SubmissionState;
use App\FileManager\FileManager;
use App\Entity\Submission;
use App\Repository\SubmissionRepository;
use App\Component\Action;

class SubmissionDetailController extends AbstractController
{
    public function __construct(private FileManager $fileManager, private SubmissionRepository $submissionRepository)
    {
    }

    #[IsGranted(new Expression('is_granted("ROLE_STUDENT") or is_granted("ROLE_TEACHER")'))]
    #[Route("/submission/show/{submission}", name: 'submission-detail')]
    public function index(Submission $submission, Request $request): Response
    {
        $teacherView = $this->isGranted('ROLE_TEACHER');
        $this->enableModule("submission-detail");
        if (!$submission->canBeViewedBy($this->getUserEntity())) {
            throw $this->createAccessDeniedException();
        }
        $timeout = $this->calcTimeout($submission);
        $zipFile = $this->fileManager->getZipFile($submission);
        if ($request->query->get("state")) {
            if ($zipFile !== null) {
                $zipFileTemplate = $this->renderView(
                    "snippets/zip-file.html.twig",
                    ["zipFile" => $zipFile]
                );
            } else {
                $zipFileTemplate = null;
            }
            return $this->json([
                "state" => $this->renderView(
                    "snippets/submission-state.html.twig",
                    [
                        "state" => $submission->getState(),
                        "isCurrent" => $submission->isCurrent(),
                    ]
                ),
                "zipFile" => $zipFileTemplate,
                "stateIsFinal" => $submission->getState()->isFinal(),
                "timeout" => $timeout,
            ]);
        } else {
            $previousSubmissionLink = null;
            $nextSubmissionLink = null;
            if ($teacherView) {
                $previousSubmissionLink = $this->changeSubmissionLink($submission, "prev");
                $nextSubmissionLink = $this->changeSubmissionLink($submission, "next");
            }
            $isDraft = $submission->getState()->isDraft();
            $heading = $teacherView ?
                "Informace o odevzdání" :
                ($isDraft ? "Rozpracované odevzdání" : "Odevzdání dokončeno")
            ;
            return $this->render("submission.html.twig", [
                "previousSubmissionLink" => $previousSubmissionLink,
                "nextSubmissionLink" => $nextSubmissionLink,
                "submission" => $submission,
                "files" => $this->fileManager->listFiles($submission),
                "timeout" => $timeout,
                "heading" => $heading,
                "zipFile" => $zipFile,
            ]);
        }
    }

    private function changeSubmissionLink(Submission $submission, string $type): string
    {
        return $this->generateUrl('change-submission', [
            'submission' => $submission->getId(),
            'type' => $type,
            '_back' => false,
        ]);
    }

    protected function backLinkEnabled(): bool
    {
        return true;
    }

    #[IsGranted(new Expression('is_granted("ROLE_STUDENT") or is_granted("ROLE_TEACHER")'))]
    #[Route("/submission/show/{submission}/files", name: 'submission-files')]
    public function files(Submission $submission, Request $request): Response
    {
        $this->enableModule("submission-detail");
        if (!$submission->canBeViewedBy($this->getUserEntity())) {
            throw $this->createAccessDeniedException();
        }

        $file = $request->query->get("file");
        if (is_string($file)) {
            $fileDescriptor = $this->fileManager->getSingleFile($submission, $file);
        } else {
            $fileDescriptor = $this->fileManager->getZipFile($submission);
        }

        if ($fileDescriptor === null) {
            throw $this->createNotFoundException();
        }
        return $fileDescriptor->getDownloadResponse();
    }

    #[IsGranted('ROLE_TEACHER')]
    #[Route("/submission/show/{submission}/change/{type}", name: 'change-submission')]
    public function changeSubmission(Submission $submission, string $type, Request $request): Response
    {
        $submission = $this->getChangedSubmission($submission, $type) ?? $submission;
        return $this->redirectToRoute('submission-detail', [
            'submission' => $submission->getId(),
            '_back' => false,
        ]);
    }

    private function getChangedSubmission(Submission $submission, string $type): ?Submission
    {
        if ($type === "prev" || $type === "next") {
            return $this->submissionRepository->getNextUserSubmission($submission, ($type === "prev") ? true : false);
        }
        return null;
    }

    private function calcTimeout(Submission $submission): int
    {
        $submittedAt = $submission->getSubmittedAt();
        if ($submittedAt === null) {
            return 10;
        }
        $diff = time() - $submittedAt->getTimestamp();
        if ($diff <= 0) {
            return 10;
        }
        if ($diff <= 3) {
            return 1;
        }
        if ($diff <= 10) {
            return 3;
        }
        if ($diff <= 30) {
            return 5;
        }
        if ($diff <= 60) {
            return 10;
        }
        if ($diff <= 300) {
            return 20;
        }
        if ($diff <= 600) {
            return 30;
        }
        return 60;
    }
}
