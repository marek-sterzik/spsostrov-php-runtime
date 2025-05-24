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

    public function lockSubmission(SubmissionDescriptor $descriptor): bool
    {
        return $this->lockOn($descriptor, false, function ($submission) {
            if ($submission === null) {
                $this->lastError = "no_submission_available";
                return false;
            }

            if (!$submission->getState()->isDraft()) {
                $this->lastError = "not_a_draft";
                return false;
            }

            if ($submission->getState()->isWritableDraft()) {
                $submission->setSubmittedAt(new DateTimeImmutable());
                $submission->setState(SubmissionState::Locked);
                $this->fileManager->putManifest($submission);
                $this->entityManager->flush();
            }

            return true;
        });
    }

    public function deleteSubmission(SubmissionDescriptor $descriptor): bool
    {
        return $this->lockOn($descriptor, false, function ($submission) {
            if ($submission === null) {
                $this->lastError = "no_submission_available";
                return false;
            }

            $submission->setState(SubmissionState::Trash);
            $this->entityManager->flush();
            $this->jobManager->invoke("remove_submission", ["id" => $submission->getId()]);
            return true;
        });
    }

    public function deleteFile(SubmissionDescriptor $descriptor, string $filename): bool
    {
        return $this->lockOn($descriptor, false, function ($submission) use ($filename) {
            if ($submission === null) {
                $this->lastError = "submission_does_not_exist";
                return false;
            }

            if (!$submission->getState()->isWritableDraft()) {
                $this->lastError = "not_a_draft";
                return false;
            }

            $error = $this->fileManager->deleteFile($submission, $filename);

            if ($error === "empty") {
                $submission->setState(SubmissionState::Trash);
                $this->entityManager->flush();
                $this->jobManager->invoke("remove_submission", ["id" => $submission->getId()]);
                $error = null;
            }

            if ($error !== null) {
                $this->lastError = $error;
                return false;
            }
            return true;
        });
    }

    public function moveFile(SubmissionDescriptor $descriptor, string $fromFilename, string $toFilename): bool
    {
        return $this->lockOn($descriptor, false, function ($submission) use ($fromFilename, $toFilename) {
            if ($submission === null) {
                $this->lastError = "submission_does_not_exist";
                return false;
            }

            if (!$submission->getState()->isWritableDraft()) {
                $this->lastError = "not_a_draft";
                return false;
            }

            $error = $this->fileManager->moveFile($submission, $fromFilename, $toFilename);

            if ($error !== null) {
                $this->lastError = $error;
                return false;
            }
            return true;
        });
    }

    public function closeLockedSubmission(SubmissionDescriptor $descriptor, bool $force): bool
    {
        return $this->lockOn($descriptor, false, function ($submission) use ($force) {
            if ($submission === null) {
                $this->lastError = "no_submission_available";
                return false;
            }
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
            if ($submission->getAssignment()->getSubmissionMode()->deleteOld()) {
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

    public function lockOn(SubmissionDescriptor $descriptor, bool $createNonexistent, callable $criticalSection): mixed
    {
        $this->lastError = null;
        $this->lastProcessedSubmission = null;
        $lock = $descriptor->getLock();
        $this->lockManager->lock($lock);
        try {
            $submission = $descriptor->getSubmission();
            if ($submission === null) {
                $submission = $this->getSubmission($descriptor, $createNonexistent);
            }
            $this->lastProcessedSubmission = $submission;
            return $criticalSection($submission);
        } finally {
            $this->lockManager->unlock($lock);
        }
    }

    private function getSubmission(SubmissionDescriptor $descriptor, bool $createNonexistent): ?Submission
    {
        $assignment = $descriptor->getAssignment();
        $submitter = $descriptor->getSubmitter();

        if (!$assignment->canSubmit($submitter)) {
            return null;
        }

        $submission = $this->submissionRepository->getLastSubmission($assignment, $submitter);
        if ($submission !== null && $submission->getState()->isDraft()) {
            return $submission;
        }

        if ($createNonexistent &&
            ($submission === null || $assignment->getSubmissionMode()->allowMultiple())
        ) {
            $submission = new Submission($assignment, $submitter);
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
