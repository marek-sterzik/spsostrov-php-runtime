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
        sleep(20);
        return $this;
    }
}
