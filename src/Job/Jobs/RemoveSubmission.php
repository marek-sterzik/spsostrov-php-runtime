<?php

namespace App\Job\Jobs;

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

    public function run(array $arguments): void
    {
        $submission = $this->submissionRepository->find($arguments['id']);
        if ($submission !== null && $submission->getState() === SubmissionState::Trash) {
            $this->fileManager->cleanup($submission);
            $this->entityManager->remove($submission);
            $this->entityManager->flush();
        }
    }
}
