<?php

namespace App\FileManager;

use Exception;
use App\Submission\SubmissionState;
use App\Entity\Submission;

class BackupOperations
{
    public function __construct(private FileOperations $fileOperations)
    {
    }

    public function backupSubmission(Submission $submission): self
    {
        throw new Exception("not yet implemented");
        return $this;
    }
}
