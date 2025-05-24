<?php

namespace App\Submission;

use DateTimeImmutable;
use App\Repository\SubmissionRepository;
use Doctrine\ORM\EntityManagerInterface as EntityManager;
use App\FileManager\FileManager;
use App\Job\JobManager;
use App\Entity\Submission;
use App\Entity\Assignment;
use App\Entity\User;
use App\Lock\LockManager;

class SubmissionManager
{
    private ?string $lastError = null;
    private ?Submission $lastProcessedSubmission = null;

    public function __construct(
        private SubmissionRepository $submissionRepository,
        private EntityManager $entityManager,
        private FileManager $fileManager,
        private JobManager $jobManager,
        private LockManager $lockManager
    ) {
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getLastProcessedSubmission(): ?Submission
    {
        return $this->lastProcessedSubmission;
    }

    public function lockSubmission(Assignment $assignment, User $user): bool
    {
        $this->lastError = null;
        $this->lastProcessedSubmission = null;
        return $this->lock($assignment, $user, function () use ($assignment, $user) {
            $submission = $this->ensureSubmissionExists($assignment, $user);
            if ($submission->getId() === null || $submission->getSubmitter() !== $user) {
                $this->lastError = "no_submission_available";
                return false;
            }
            $this->lastProcessedSubmission = $submission;

            if ($submission->getState()->isWritableDraft()) {
                $submission->setSubmittedAt(new DateTimeImmutable());
                $submission->setState(SubmissionState::Locked);
                $this->fileManager->putManifest($submission);
                $this->entityManager->flush();
            }

            return true;
        });
    }

    public function deleteSubmission(Submission $submission): bool
    {
        $this->lastError = null;
        $this->lastProcessedSubmission = null;
        return $this->lock($submission->getAssignment(), $submission->getSubmitter(), function () use ($submission) {
            $submission->setState(SubmissionState::Trash);
            $this->entityManager->flush();
            $this->jobManager->invoke("remove_submission", ["id" => $submission->getId(), "force" => true]);
            return true;
        });
    }

    public function closeLockedSubmission(Assignment $assignment, User $user, bool $force): bool
    {
        $this->lastError = null;
        $this->lastProcessedSubmission = null;
        return $this->lock($assignment, $user, function () use ($assignment, $user, $force) {
            $submission = $this->ensureSubmissionExists($assignment, $user);
            if ($submission->getId() === null ||
                $submission->getSubmitter() !== $user
            ) {
                $this->lastError = "no_submission_available";
                return false;
            }
            $this->lastProcessedSubmission = $submission;
            if (!$submission->getState()->isLockedDraft()) {
                $this->lastError = "submission_not_locked";
                return false;
            }
            $lateOverriddenSubmission = $this->getLateOverriddenSubmission($submission);
            if ($lateOverriddenSubmission !== null && !$force) {
                $this->lastError = $lateOverriddenSubmission->isSubmittedLate() ?
                    'force_required_override_late' :
                    'force_required_override_early'
                ;
                return false;
            }

            $submission->setState(SubmissionState::Submitted);
            $this->fileManager->putManifest($submission);
            $this->entityManager->flush();
            if ($assignment->getSubmissionMode()->deleteOld()) {
                $submissions = [];
                foreach ($this->submissionRepository->selectCurrentFor($submission) as $deactivatedSubmission) {
                    $deactivatedSubmission->setCurrent(false);
                    $submissions[] = $deactivatedSubmission->getId();
                }
                if (!empty($submissions)) {
                    $this->entityManager->flush();
                    foreach ($submissions as $submissionId) {
                        $this->jobManager->invoke("remove_submission", ["id" => $submissionId, "force" => true]);
                    }
                }
            } else {
                $this->submissionRepository->updateCurrentFor($submission);
            }
            $this->jobManager->invoke("close_submission", ["id" => $submission->getId()]);
            return true;
        });
    }

    public function lock(Assignment $assignment, User $user, callable $criticalSection): mixed
    {
        $lock = sprintf("sc-%d-%d", $assignment->getId(), $user->getId());
        $this->lockManager->lock($lock);
        try {
            return $criticalSection();
        } finally {
            $this->lockManager->unlock($lock);
        }
    }

    public function ensureSubmissionExists(Assignment $assignment, User $user): ?Submission
    {
        if (!$assignment->canSubmit($user)) {
            return null;
        }
        $submission = $this->submissionRepository->getLastSubmission($assignment, $user);
        if ($submission !== null && $submission->getState()->isDraft()) {
            return $submission;
        }

        if ($submission === null || $assignment->getSubmissionMode()->allowMultiple()) {
            $submission = new Submission($assignment, $user);
            $this->entityManager->persist($submission);
            return $submission;
        }

        return null;
    }

    private function getLateOverriddenSubmission(Submission $submission): ?Submission
    {
        if (!$submission->getAssignment()->getSubmissionMode()->deleteOld()) {
            return null;
        }
        if (!$submission->isSubmittedLate()) {
            return null;
        }
        return $this->submissionRepository->getEarlierSubmission($submission);
    }
}
