<?php

namespace App\Controller;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use App\Submission\SubmissionState;
use App\Assignment\AssignmentState;
use App\Form\FileSubmitType;
use App\Repository\SubmissionRepository;
use App\Entity\Assignment;
use App\Entity\Submission;
use App\Entity\User;
use App\Lock\LockManager;
use App\FileManager\FileManager;
use App\Job\JobManager;

class SubmissionController extends AbstractController
{
    const ERRORS = [
        "submission_is_closed" => "Odevzdání už je uzavřeno a nelze jej měnit.",
        "delete_file_failed" => "Nelze smazat soubor.",
        "invlid_file_name" => "Neplatný název souboru.",
        "moved_file_does_not_exist" => "Přejmenovávaný soubor neexistuje.",
        "destination_file_already_exist" => "Cílovy soubor už existuje.",
        "move_file_failed" => "Nelze přejmenovat soubor.",
    ];
    public function __construct(
        private SubmissionRepository $submissionRepository,
        private LockManager $lockManager,
        private FileManager $fileManager,
        private JobManager $jobManager,
    ) {
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/{assignment}", name: 'create-submission')]
    public function index(Assignment $assignment, Request $request): Response
    {
        $error = $request->query->get('err');
        if (!is_string($error)) {
            $error = null;
        }
        $errorMessage = null;
        if ($error !== null) {
            $errorMessage = self::ERRORS[$error] ?? sprintf("Nastala neznámá chyba: %s.",  $error);
        }
        $this->enableModule("upload-files");
        $user = $this->getUserEntity();
        if ($user === null) {
            return $this->redirectBack(true);
        }
        $lock = sprintf("sc-%d-%d", $assignment->getId(), $user->getId());
        $this->lockManager->lock($lock);
        try {
            $submission = $this->ensureSubmissionExists($assignment, $user);
            if ($submission === null) {
                return $this->redirectBack(true);
            }
            return $this->form(FileSubmitType::class, [], ["attr" => ["class" => "with-progress"]])
            ->action("nahrát soubory", function (array $data) use ($submission) {
                $this->submitFiles($data['file'], $submission);
                return $this->redirect($this->getRequest()->getRequestUri());
            })
            ->caption("Nahrát soubory")
            ->useTemplate("upload.html.twig", [
                "files" => $this->fileManager->listFiles($submission),
                "assignment" => $assignment,
                "errorCode" => $error,
                "errorMessage" => $errorMessage,
            ])
            ->handle()
            ;
        } finally {
            $this->lockManager->unlock($lock);
        }
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/{assignment}/files", name: 'submission-file-action')]
    public function fileAction(Assignment $assignment, Request $request): Response
    {
        $user = $this->getUserEntity();
        $submission = $this->ensureSubmissionExists($assignment, $user);
        $error = null;
        if ($submission->getId() !== null && $submission->getSubmitter() === $user) {
            $deleteFile = $request->query->get("delete");
            if (is_string($deleteFile)) {
                $error = $this->fileManager->deleteFile($submission, $deleteFile);
            }
            $mvFrom = $request->query->get("mvfrom");
            $mvTo = $request->query->get("mvto");
            if (is_string($mvFrom) && is_string($mvTo)) {
                $error = $this->fileManager->moveFile($submission, $mvFrom, $mvTo);
            }
        }
        $routeParams = [
            "assignment" => $assignment->getId(),
            "_back" => false,
        ];
        if ($error) {
            $routeParams['err'] = $error;
        }
        return $this->redirectToRoute('create-submission', $routeParams);
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/{assignment}/close", name: 'submission-close')]
    public function closeAction(Assignment $assignment, Request $request): Response
    {
        $user = $this->getUserEntity();
        $submission = $this->ensureSubmissionExists($assignment, $user);
        if ($submission->getId() === null || $submission->getSubmitter() !== $user) {
            return $this->redirectToRoute('create-submission', [
                "assignment" => $assignment->getId(),
                "_back" => false,
            ]);
        }
        $submission->setSubmittedAt(new DateTimeImmutable());
        $submission->setState(SubmissionState::Submitted);
        $this->fileManager->putManifest($submission);
        $this->getEntityManager()->flush();
        $this->jobManager->invoke("close_submission", ["id" => $submission->getId()]);
        return $this->redirectToRoute('submission-detail', ["submission" => $submission->getId(), "_back" => false]);
    }

    private function submitFiles(array $files, Submission $submission): void
    {
        if ($submission->getId() === null) {
            $this->getEntityManager()->flush();
        }
        $this->fileManager->addFiles($submission, $files);
    }

    private function ensureSubmissionExists(Assignment $assignment, User $user): ?Submission
    {
        if ($assignment->getState() !== AssignmentState::Active) {
            return null;
        }
        $submission = $this->submissionRepository->getLastSubmission($assignment, $user);
        if ($submission !== null && $submission->getState() === SubmissionState::Draft) {
            return $submission;
        }

        if ($submission === null || $assignment->getSubmissionMode()->allowMultiple()) {
            $submission = new Submission($assignment, $user);
            $this->getEntityManager()->persist($submission);
            return $submission;
        }

        return null;
    }

    protected function getDefaultBackUrl(): string
    {
        return $this->generateUrl("submit");
    }
}
