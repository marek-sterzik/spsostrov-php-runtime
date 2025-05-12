<?php

namespace App\Job;

use Exception;
use App\Utility\Uuid;

class JobManager
{
    public function __construct(private JobDir $jobDir)
    {
    }

    public function invoke(string $job, array $arguments = []): string
    {
        $uuid = Uuid::uuid6()->toString();
        $data = [
            "job" => $job,
            "arguments" => $arguments,
        ];
        $this->jobDir->job($uuid)->create($data);
        return $uuid;
    }
}
