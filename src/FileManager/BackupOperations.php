<?php

namespace App\FileManager;

use Exception;
use App\Submission\SubmissionState;
use App\Entity\Submission;
use App\Sync\Sync;

class BackupOperations
{
    public function __construct(private FileOperations $fileOperations, private Sync $sync)
    {
    }

    public function backupSubmission(Submission $submission): bool
    {
        if ($this->sync->isEnabled()) {
            list($storageDir, $relativeDir, $filename) = $this->getBackupLocation($submission);
            $this->sync->syncFile($storageDir, $relativeDir, $filename);
            return true;
        } else {
            return false;
        }
    }

    public function cleanup(Submission $submission): void
    {
        if ($this->sync->isEnabled()) {
            list($storageDir, $relativeDir, $filename) = $this->getBackupLocation($submission);
            $this->sync->removeFile($storageDir, $relativeDir, $filename);
        }
    }

    private function getBackupLocation(Submission $submission): array
    {
        $storageDir = $this->fileOperations->getStorageDir();
        $zipArchive = $this->fileOperations->getSubmissionZipArchive($submission, false, true);
        $filename = basename($zipArchive);
        $relativeDir = dirname($zipArchive);
        return [$storageDir, $relativeDir, $filename];
    }
}
