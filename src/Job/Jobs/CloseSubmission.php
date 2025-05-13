<?php

namespace App\Job\Jobs;

use Doctrine\ORM\EntityManagerInterface as EntityManager;
use App\Repository\SubmissionRepository;
use App\FileManager\FileManager;
use App\Submission\SubmissionState;

class CloseSubmission extends AbstractJob
{
    public function __construct(
        private SubmissionRepository $submissionRepository,
        private EntityManager $entityManager,
        private FileManager $fileManager
    ) {
    }

    public static function getName(): string
    {
        return 'close_submission';
    }

    public function run(array $arguments): void
    {
        $submission = $this->submissionRepository->find($arguments['id']);
        if ($submission !== null && $submission->getState() === SubmissionState::Submitted) {
            $this->fileManager->pack($submission);
            $submission->setState(SubmissionState::Packed);
            $this->entityManager->flush();
        }
    }
}
