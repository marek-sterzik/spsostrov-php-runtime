<?php

namespace App\Cron;

use App\Repository\AssignmentRepository;
use App\Repository\SubmissionRepository;
use Doctrine\ORM\EntityManagerInterface as EntityManager;

class CronManager
{
    public function __construct(
        private AssignmentRepository $assignmentRepository,
        private SubmissionRepository $submissionRepository,
        private EntityManager $entityManager
    ) {
    }

    public function assignmentsCronTasks(): void
    {
        $this->assignmentRepository->updateStates();
    }

    public function allTasks(): void
    {
        $this->assignmentsCronTasks();
    }
}
