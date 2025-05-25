<?php

namespace App\Cron;

use App\Repository\AssignmentRepository;
use App\Repository\SubmissionRepository;
use App\Submission\SubmissionManager;

class CronManager
{
    public function __construct(
        private AssignmentRepository $assignmentRepository,
        private SubmissionRepository $submissionRepository,
        private SubmissionManager $submissionManager
    ) {
    }

    public function assignmentsCronTasks(): void
    {
        $this->assignmentRepository->updateStates();
    }

    public function allTasks(): void
    {
        $this->assignmentsCronTasks();
        $this->applyMissedDraftPolicy();
    }

    private function applyMissedDraftPolicy(): void
    {
        foreach ($this->submissionRepository->findLockedInactiveSubmissions() as $submission) {
            $descriptor = $submission->getSubmissionDescriptor();
            if (!$this->submissionManager->closeLockedSubmission($descriptor, false)) {
                $this->submissionManager->applyMissedDraftPolicy($descriptor);
            }
        }

        foreach ($this->submissionRepository->findDraftInactiveSubmissions() as $submission) {
            $this->submissionManager->applyMissedDraftPolicy($submission->getSubmissionDescriptor());
        }
    }
}
