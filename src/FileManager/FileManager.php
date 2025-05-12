<?php

namespace App\FileManager;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Exception;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\EntityManagerInterface as EntityManager;
use App\Submission\SubmissionState;
use App\Entity\Submission;
use App\Lock\LockManager;
use App\Job\JobManager;

class FileManager
{
    const MANIFEST_NAME = "_manifest.yaml";

    public function __construct(
        private string $storageDir,
        private LockManager $lockManager,
        private EntityManager $entityManager,
        private JobManager $jobManager
    ) {
    }

    public function putManifest(Submission $submission): self
    {
        $data = $submission->getManifest();
        $data = Yaml::dump($data, 3) . "\n";
        $this->locked($submission, function () use ($submission, $data) {
            $submissionDirectory = $this->getSubmissionDirectory($submission);
            $this->ensureDirectoryExists($submissionDirectory);
            file_put_contents($submissionDirectory . "/" . self::MANIFEST_NAME, $data);
        });
        return $this;
    }

    public function listFiles(Submission $submission): array
    {
        if ($submission->getId() === null) {
            return [];
        }
        $submissionDirectory = $this->getSubmissionDirectory($submission);
        $files = $this->listFilesRaw($submissionDirectory);
        sort($files);
        return array_map(fn ($file) => new FileDescriptor($submissionDirectory, $file), $files);
    }

    private function listFilesRaw(string $submissionDirectory): array
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
            if ($file === "." || $file === ".." || $file === self::MANIFEST_NAME) {
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

    public function deleteFile(Submission $submission, string $filename): ?string
    {
        if ($submission->getState() !== SubmissionState::Draft) {
            return 'submission_is_closed';
        }
        $filename = $this->canonizeFilename($filename);
        if ($filename !== null) {
            $ret = $this->locked($submission, function () use ($submission, $filename) {
                $submissionDirectory = $this->getSubmissionDirectory($submission);
                $file = $submissionDirectory . "/" . $filename;
                if (!@unlink($file)) {
                    return 'delete_file_failed';
                }

                if (empty($this->listFilesRaw($submissionDirectory))) {
                    $submission->setState(SubmissionState::Trash);
                    $this->entityManager->flush();
                    $this->jobManager->invoke("remove_submission", ["id" => $submission->getId()]);
                }

                return null;
            });
        } else {
            $ret = 'invalid_file_name';
        }
        return $ret;
    }

    public function moveFile(Submission $submission, string $fileFrom, string $fileTo): ?string
    {
        if ($submission->getState() !== SubmissionState::Draft) {
            return 'submission_is_closed';
        }
        $fileFrom = $this->canonizeFilename($fileFrom);
        $fileTo = $this->canonizeFilename($fileTo);
        if ($fileFrom !== null && $fileTo !== null) {
            $ret = $this->locked($submission, function () use ($submission, $fileFrom, $fileTo) {
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
            });
        } else {
            $ret = 'invalid_file_name';
        }
        return $ret;
    }

    public function cleanup(Submission $submission): self
    {
        $this->locked($submission, function () use ($submission) {
            $submissionDirectory = $this->getSubmissionDirectory($submission);
            $this->rmRf($submissionDirectory);
            foreach ($this->getParentDirectories($submission) as $dir) {
                @rmdir($dir);
            }
        });
        return $this;
    }

    private function rmRf(string $directory): self
    {
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
        $filename = $this->findFreeFilename($dir, $filename);
        $uploadedFile->move($dir, $filename);
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
                $filename = substr($filename, strlen($filename) - strlen($matches[0]));
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
        $submissionId = $submission->getUuid();
        return sprintf("%s/%s", $this->getSubmissionUserDirectory($submission), $submissionId);
    }

    private function getSubmissionUserDirectory(Submission $submission): string
    {
        $userId = $submission->getSubmitter()->getId();
        return sprintf("%s/%d", $this->getAssignmentDirectory($submission), $userId);
    }

    private function getAssignmentDirectory(Submission $submission): string
    {
        $assignmentId = $submission->getAssignment()->getId();
        return sprintf("%s/%d", $this->storageDir, $assignmentId);
    }

    private function getParentDirectories(Submission $submission): array
    {
        return [
            $this->getSubmissionUserDirectory($submission),
            $this->getAssignmentDirectory($submission),
        ];
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
