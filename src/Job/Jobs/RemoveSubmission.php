<?php

namespace App\Job\Jobs;

use Exception;
use Doctrine\ORM\EntityManagerInterface as EntityManager;
use App\Repository\SubmissionRepository;
use App\FileManager\FileManager;
use App\Submission\SubmissionState;

class RemoveSubmission extends AbstractJob
{
    public function __construct(
        private SubmissionRepository $submissionRepository,
        private EntityManager $entityManager,
        private FileManager $fileManager
    ) {
    }

    public static function getName(): string
    {
        return 'remove_submission';
    }

    public function run(array $arguments): ?array
    {
        $submission = $this->submissionRepository->find($arguments['id']);
        $force = ($arguments['force'] ?? false) ? true : false;
        if ($submission !== null) {
            if ($force && $submission->getState() !== Submission::Trash) {
                if (!$submission->getState()->isFinal()) {
                    throw new Exception("Job not yet finished, cannot be deleted");
                }
                $submission->setState(SubmissionState::Trash);
                $this->entityManager->flush();
            }
            if ($submission->getState() === SubmissionState::Trash) {
                $this->fileManager->cleanup($submission);
                $this->entityManager->remove($submission);
                $this->entityManager->flush();
            }
        }
        return null;
    }
}
