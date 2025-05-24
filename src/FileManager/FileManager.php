<?php

namespace App\FileManager;

use App\Submission\SubmissionState;
use App\Entity\Submission;
use App\Lock\LockManager;

class FileManager
{
    public function __construct(
        private LockManager $lockManager,
        private FileOperations $fileOperations,
        private ZipOperations $zipOperations,
        private BackupOperations $backupOperations
    ) {
    }

    public function putManifest(Submission $submission): self
    {
        $this->locked($submission, function () use ($submission) {
            $this->fileOperations->putManifest($submission);
        });
        return $this;
    }

    public function listFiles(Submission $submission): array
    {
        return $this->locked($submission, function () use ($submission) {
            return $this->zipOperations->listFiles($submission) ?? $this->fileOperations->listFiles($submission);
        }, false);
    }

    public function getSingleFile(Submission $submission, string $filename): ?FileDescriptor
    {
        return $this->locked($submission, function () use ($submission, $filename) {
            return $this->zipOperations->getSingleFile($submission, $filename) ??
                $this->fileOperations->getSingleFile($submission, $filename);
        }, false);
    }

    public function getZipFile(Submission $submission): ?FileDescriptor
    {
        return $this->locked($submission, function () use ($submission) {
            return $this->fileOperations->getZipFile($submission);
        }, false);
    }

    public function backupSubmission(Submission $submission): bool
    {
        return $this->locked($submission, function () use ($submission) {
            return $this->backupOperations->backupSubmission($submission);
        }, false);
    }

    public function addFiles(Submission $submission, array $uploadedFiles): self
    {
        $this->locked($submission, function () use ($submission, $uploadedFiles) {
            $this->fileOperations->addFiles($submission, $uploadedFiles);
        });
        return $this;
    }

    public function deleteFile(Submission $submission, string $filename): ?string
    {
        return $this->locked($submission, function () use ($submission, $filename) {
            return $this->fileOperations->deleteFile($submission, $filename);
        });
    }

    public function moveFile(Submission $submission, string $fileFrom, string $fileTo): ?string
    {
        return $this->locked($submission, function () use ($submission, $fileFrom, $fileTo) {
            return $this->fileOperations->moveFile($submission, $fileFrom, $fileTo);
        });
    }

    public function cleanup(Submission $submission): self
    {
        $this->locked($submission, function () use ($submission) {
            $this->fileOperations->cleanup($submission, true);
            $this->backupOperations->cleanup($submission);
        });
        return $this;
    }

    public function pack(Submission $submission): self
    {
        $this->locked($submission, function () use ($submission) {
            $this->zipOperations->pack($submission);
        });
        return $this;
    }

    private function locked(Submission $submission, callable $innerFunction, bool $write = true): mixed
    {
        if ($write) {
            $lockId = sprintf("sf-%d", $submission->getId());
            $this->lockManager->lock($lockId);
            try {
                $ret = $innerFunction();
            } finally {
                $this->lockManager->unlock($lockId);
            }
            return $ret;
        } else {
            return $innerFunction();
        }
    }
}
