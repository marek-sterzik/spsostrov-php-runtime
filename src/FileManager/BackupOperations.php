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
        $zipArchive = $this->fileOperations->getSubmissionZipArchive($submission, false, false);
        $zipArchiveRelative = $this->fileOperations->getSubmissionZipArchive($submission, false, true);
        sleep(20);
        return $this;
    }
}
