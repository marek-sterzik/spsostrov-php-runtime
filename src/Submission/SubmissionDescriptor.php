<?php

namespace App\Submission;

use App\Entity\Submission;
use App\Entity\Assignment;
use App\Entity\User;

class SubmissionDescriptor
{
    public static function submission(Submission $submission): self
    {
        return new self(null, null, $submission);
    }

    public static function assignmentUserDraft(Assignment $assignment, User $user): self
    {
        return new self($assignment, $user, null);
    }

    private function __construct(
        private ?Assignment $assignment,
        private ?User $user,
        private ?Submission $submission
    ) {
    }

    public function getAssignment(): Assignment
    {
        return $this->assignment ?? $this->submission->getAssignment();
    }

    public function getSubmitter(): User
    {
        return $this->user ?? $this->submission->getSubmitter();
    }

    public function getSubmission(): ?Submission
    {
        return $this->submission;
    }

    public function getLock(): string
    {
        $assignmentId = $this->getAssignment()->getId();
        $userId = $this->getSubmitter()->getId();
        return sprintf("sc-%d-%d", $assignmentId, $userId);
    }
}
