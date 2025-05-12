<?php

namespace App\FileManager;

use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Submission\SubmissionState;
use App\Entity\Submission;
use App\Lock\LockManager;

class FileManager
{
    public function __construct(private string $storageDir, private LockManager $lockManager)
    {
    }

    public function listFiles(Submission $submission): array
    {
        if ($submission->getId() === null) {
            return [];
        }
        $submissionDirectory = $this->getSubmissionDirectory($submission);
        if (!is_dir($submissionDirectory)) {
            return [];
        }
        $dd = opendir($submissionDirectory);
        if (!$dd) {
            return [];
        }
        $files = [];
        while (($file = readdir($dd)) !== false) {
            if ($file === "." || $file === "..") {
                continue;
            }
            if (!is_file($submissionDirectory . "/" . $file)) {
                continue;
            }
            $files[] = $file;
        }
        closedir($dd);
        sort($files);
        return array_map(fn ($file) => new FileDescriptor($submissionDirectory, $file), $files);
    }

    public function addFiles(Submission $submission, array $uploadedFiles): self
    {
        if ($submission->getState() !== SubmissionState::Draft) {
            throw new Exception("Files can be uploaded only to submission drafts");
        }
        $this->locked($submission, function () use ($submission, $uploadedFiles) {
            $submissionDirectory = $this->getSubmissionDirectory($submission);
            $this->ensureDirectoryExists($submissionDirectory);
            foreach ($uploadedFiles as $uploadedFile) {
                $this->uploadFile($submissionDirectory, $uploadedFile);
            }
        });
        return $this;
    }

    public function deleteFile(Submission $submission, string $filename): self
    {
        if ($submission->getState() !== SubmissionState::Draft) {
            throw new Exception("Files can be uploaded only to submission drafts");
        }
        $filename = $this->canonizeFilename($filename);
        if ($filename !== null) {
            $this->locked($submission, function () use ($submission, $filename) {
                $submissionDirectory = $this->getSubmissionDirectory($submission);
                $file = $submissionDirectory . "/" . $filename;
                @unlink($file);
            });
        }
        return $this;
    }

    public function moveFile(Submission $submission, string $fileFrom, string $fileTo): self
    {
        if ($submission->getState() !== SubmissionState::Draft) {
            throw new Exception("Files can be uploaded only to submission drafts");
        }
        $fileFrom = $this->canonizeFilename($fileFrom);
        $fileTo = $this->canonizeFilename($fileTo);
        if ($fileFrom !== null && $fileTo !== null) {
            $this->locked($submission, function () use ($submission, $fileFrom, $fileTo) {
                $submissionDirectory = $this->getSubmissionDirectory($submission);
                $fileFrom = $submissionDirectory . "/" . $fileFrom;
                $fileTo = $submissionDirectory . "/" . $fileTo;
                if (is_file($fileFrom) && !is_file($fileTo)) {
                    @rename($fileFrom, $fileTo);
                }
            });
        }
        return $this;
    }

    private function canonizeFilename(string $filename): ?string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[[:cntrl:]]/', '', $filename);
        if ($filename === "" || $filename === "." || $filename === "..") {
            return null;
        }
        return $filename;
    }

    private function uploadFile(string $dir, UploadedFile $uploadedFile): void
    {
        $filename = $this->canonizeFilename($uploadedFile->getClientOriginalName()) ?? "uploaded_file";
        $uploadedFile->move($dir, $filename);
    }

    private function locked(Submission $submission, callable $innerFunction): mixed
    {
        $lockId = sprintf("sf-%d", $submission->getId());
        $this->lockManager->lock($lockId);
        try {
            $ret = $innerFunction();
        } finally {
            $this->lockManager->unlock($lockId);
        }
        return $ret;
    }

    private function getSubmissionDirectory(Submission $submission): string
    {
        $assignmentId = $submission->getAssignment()->getId();
        $userId = $submission->getSubmitter()->getId();
        $submissionId = $submission->getUuid();
        return sprintf("%s/%d/%d/%s", $this->storageDir, $assignmentId, $userId, $submissionId);
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
            if (!is_dir($directory)) {
                throw new Exception("Cannot create directory: $directory");
            }
        }
    }
}
