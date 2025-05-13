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
        private LockManager $lockManager,
        private EntityManager $entityManager,
        private JobManager $jobManager,
        private FileOperations $fileOperations
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
            return $this->fileOperations->listFiles($submission);
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
            $code = $this->fileOperations->deleteFile($submission, $filename);
            if ($code === "empty") {
                $submission->setState(SubmissionState::Trash);
                $this->entityManager->flush();
                $this->jobManager->invoke("remove_submission", ["id" => $submission->getId()]);
                return null;
            }
            return $code;
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
            $this->fileOperations->cleanup($submission);
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
