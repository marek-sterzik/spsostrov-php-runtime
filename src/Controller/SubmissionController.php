<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Submission\SubmissionState;
use App\Assignment\AssignmentState;
use App\Form\FileSubmitType;
use App\Repository\SubmissionRepository;
use App\Entity\Assignment;
use App\Entity\Submission;
use App\Lock\LockManager;

class SubmissionController extends AbstractController
{
    public function __construct(
        private SubmissionRepository $submissionRepository,
        private LockManager $lockManager
    ) {
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/{submission}", name: 'edit-submission')]
    public function index(Submission $submission): Response
    {
        return $this->form(FileSubmitType::class, [], ["attr" => ["class" => "with-progress"]])
        ->action("Uložit", function (array $data) {
            return $this->redirectBack(true);
        })
        ->action("Zrušit", function (array $data) {
            return $this->redirectBack(true);
        }, type: 'btn-secondary', validated: false)
        ->handle()
        ;
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/create-submission/{assignment}", name: "create-submission")]
    public function createSubmission(Assignment $assignment): Response
    {
        $lock = sprintf("sc-%d", $assignment->getId());
        $this->lockManager->lock($lock);
        $submission = null;
        try {
            $submission = $this->ensureSubmissionExists($assignment);
        } finally {
            $this->lockManager->unlock($lock);
        }
        if ($submission !== null) {
            return $this->redirectToRoute("edit-submission", [
                "submission" => $submission->getId(),
                "_back" => false
            ]);
        } else {
            return $this->redirectBack(true);
        }
    }

    private function ensureSubmissionExists(Assignment $assignment): ?Submission
    {
        $user = $this->getUserEntity();
        if ($user === null) {
            return null;
        }
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
            $this->getEntityManager()->flush();
            return $submission;
        }

        return null;
    }

    protected function getDefaultBackUrl(): string
    {
        return $this->generateUrl("submit");
    }
}
