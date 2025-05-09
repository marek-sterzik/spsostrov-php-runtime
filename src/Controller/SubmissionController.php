<?php

namespace App\Controller;

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

class SubmissionController extends AbstractController
{
    public function __construct(
        private SubmissionRepository $submissionRepository,
        private LockManager $lockManager,
        private FileManager $fileManager
    ) {
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/{assignment}", name: 'create-submission')]
    public function index(Assignment $assignment): Response
    {
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
                "files" => $this->fileManager->listFiles($submission)
            ])
            ->handle()
            ;
        } finally {
            $this->lockManager->unlock($lock);
        }
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
