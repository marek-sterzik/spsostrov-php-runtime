<?php

namespace App\Cron;

use App\Repository\AssignmentRepository;

class CronManager
{
    public function __construct(private AssignmentRepository $assignmentRepository)
    {
    }

    public function assignmentsCronTasks(): void
    {
        $this->assignmentRepository->updateStates();
    }

    public function allTasks(): void
    {
    }
}
