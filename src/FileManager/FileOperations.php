<?php

namespace App\FileManager;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Exception;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Submission\SubmissionState;
use App\Entity\Submission;

class FileOperations
{
    const MANIFEST_NAME = "_manifest.yaml";

    public function __construct(private string $storageDir)
    {
    }

    public function putManifest(Submission $submission): self
    {
        $directory = $this->getSubmissionDirectory($submission);
        $this->ensureDirectoryExists($directory);
        $manifest = Yaml::dump($submission->getManifest(), 3) . "\n";
        file_put_contents($directory . "/" . self::MANIFEST_NAME, $manifest);
        return $this;
    }

    public function getSubmissionZipArchive(
        Submission $submission,
        bool $temporary = false,
        bool $relative = false
    ): string {
        $dir = $this->getSubmissionDirectory($submission, $relative);
        return sprintf("%s%s", $dir, $temporary ? '.tmp.zip' : '.zip');
    }

    public function getStorageDir(): string
    {
        return $this->storageDir;
    }

    public function listFiles(Submission $submission, bool $includingManifest = false): array
    {
        if ($submission->getId() === null) {
            return [];
        }
        $submissionDirectory = $this->getSubmissionDirectory($submission);
        $files = $this->listFilesRaw($submissionDirectory, $includingManifest);
        sort($files);
        return array_map(fn ($file) => $this->createFileDescriptor($file, $submissionDirectory, $submission), $files);
    }

    public function getSingleFile(Submission $submission, string $filename): ?FileDescriptor
    {
        if ($submission->getId() === null) {
            return null;
        }
        $filename = $this->canonizeFilename($filename);
        if ($filename === null) {
            return null;
        }
        $submissionDirectory = $this->getSubmissionDirectory($submission);
        if (!is_file($submissionDirectory . "/" . $filename)) {
            return null;
        }
        return $this->createFileDescriptor($filename, $submissionDirectory, $submission);
    }

    public function getZipFile(Submission $submission): ?FileDescriptor
    {
        if ($submission->getId() === null) {
            return null;
        }
        $zipFile = $this->getSubmissionZipArchive($submission, false);
        if (!is_file($zipFile)) {
            return null;
        }
        return (new FileDescriptorFile($submission->getZipFileName(), $zipFile))->setSubmission($submission);
    }

    private function createFileDescriptor(string $filename, string $directory, Submission $submission): FileDescriptor
    {
        return (new FileDescriptorFile($filename, $directory . "/" . $filename))->setSubmission($submission);
    }

    public function addFiles(Submission $submission, array $uploadedFiles): self
    {
        if (!$submission->getState()->isWritableDraft()) {
            throw new Exception("Files can be uploaded only to submission drafts");
        }
        $submissionDirectory = $this->getSubmissionDirectory($submission);
        $this->ensureDirectoryExists($submissionDirectory);
        foreach ($uploadedFiles as $uploadedFile) {
            $this->uploadFile($submissionDirectory, $uploadedFile);
        }
        return $this;
    }

    public function deleteFile(Submission $submission, string $filename): ?string
    {
        if (!$submission->getState()->isWritableDraft()) {
            return 'submission_is_closed';
        }
        $filename = $this->canonizeFilename($filename);
        if ($filename !== null) {
            $submissionDirectory = $this->getSubmissionDirectory($submission);
            $file = $submissionDirectory . "/" . $filename;
            if (!@unlink($file)) {
                return 'delete_file_failed';
            }

            if (empty($this->listFilesRaw($submissionDirectory))) {
                return "empty";
            }

            return null;
        } else {
            return 'invalid_file_name';
        }
    }

    public function moveFile(Submission $submission, string $fileFrom, string $fileTo): ?string
    {
        if (!$submission->getState()->isWritableDraft()) {
            return 'submission_is_closed';
        }
        $fileFrom = $this->canonizeFilename($fileFrom);
        $fileTo = $this->canonizeFilename($fileTo);
        if ($fileFrom !== null && $fileTo !== null) {
            $submissionDirectory = $this->getSubmissionDirectory($submission);
            $fullFileFrom = $submissionDirectory . "/" . $fileFrom;
            $fullFileTo = $submissionDirectory . "/" . $fileTo;
            if (!is_file($fullFileFrom)) {
                return 'moved_file_does_not_exist';
            }
            if (!$this->filenameIsFree($submissionDirectory, $fileTo)) {
                return 'destination_file_already_exist';
            }
            if (!@rename($fullFileFrom, $fullFileTo)) {
                return 'move_file_failed';
            }
            return null;
        } else {
            return 'invalid_file_name';
        }
    }

    public function cleanup(Submission $submission, bool $includeZip): self
    {
        $submissionDirectory = $this->getSubmissionDirectory($submission);
        if ($includeZip) {
            @unlink($this->getSubmissionZipArchive($submission, true));
            @unlink($this->getSubmissionZipArchive($submission, false));
        }
        $this->rmRf($submissionDirectory);
        foreach ($this->getParentDirectories($submission) as $dir) {
            @rmdir($dir);
        }
        return $this;
    }

    public function canonizeFilename(string $filename): ?string
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
        $filename = $this->findFreeFilename($dir, $filename);
        $uploadedFile->move($dir, $filename);
    }

    private function listFilesRaw(string $submissionDirectory, bool $includingManifest = false): array
    {
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
            if (!$includingManifest && $file === self::MANIFEST_NAME) {
                continue;
            }
            if (!is_file($submissionDirectory . "/" . $file)) {
                continue;
            }
            $files[] = $file;
        }
        closedir($dd);
        return $files;
    }

    private function rmRf(string $directory): self
    {
        if (is_dir($directory)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                if ($fileinfo->isDir()) {
                    @rmdir($fileinfo->getRealPath());
                } else {
                    @unlink($fileinfo->getRealPath());
                }
            }
            @rmdir($directory);
        } else {
            @unlink($directory);
        }

        return $this;
    }

    private function findFreeFilename(string $dir, string $filename): string
    {
        $extension = '';
        if (preg_match('/(\.[a-zA-Z0-9]{1,6})+$/', $filename, $matches)) {
            $extension = $matches[0];
            $filename = substr($filename, 0, strlen($filename) - strlen($extension));
        }
        $num = 0;
        if (preg_match('/-([1-9][0-9]*)$/', $filename, $matches)) {
            $num = (int)$matches[1];
            if (((string)$num) === $matches[1]) {
                $filename = substr($filename, 0, strlen($filename) - strlen($matches[0]) - 1);
            } else {
                $num = 0;
            }
        }
        do {
            $finalFilename = sprintf("%s%s%s", $filename, ($num === 0) ? '' : ("-" . $num), $extension);
            $num++;
        } while (!$this->filenameIsFree($dir, $finalFilename));
        return $finalFilename;
    }

    private function filenameIsFree(string $dir, string $filename): bool
    {
        if ($filename === "." || $filename === ".." || $filename === "") {
            return false;
        }
        if ($filename === self::MANIFEST_NAME) {
            return false;
        }
        if (is_file($dir . "/" . $filename)) {
            return false;
        }
        return true;
    }

    private function getSubmissionUserDirectory(Submission $submission, bool $relative = false): string
    {
        $userId = $submission->getSubmitter()->getId();
        return sprintf("%s/%d", $this->getAssignmentDirectory($submission, $relative), $userId);
    }

    private function getAssignmentDirectory(Submission $submission, bool $relative = false): string
    {
        $assignment = $submission->getAssignment();
        $schoolYear = $assignment->getSchoolYear();
        $relativeDir = sprintf("%d-%02d/%d", $schoolYear, ($schoolYear + 1) % 100, $assignment->getId());
        return $relative ? $relativeDir : sprintf("%s/%s", $this->storageDir, $relativeDir);
    }

    private function getParentDirectories(Submission $submission, bool $relative = false): array
    {
        return [
            $this->getSubmissionUserDirectory($submission, $relative),
            $this->getAssignmentDirectory($submission, $relative),
        ];
    }

    private function getSubmissionDirectory(Submission $submission, bool $relative = false): string
    {
        $submissionId = $submission->getUuid();
        return sprintf("%s/%s", $this->getSubmissionUserDirectory($submission, $relative), $submissionId);
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
