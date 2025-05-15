<?php

namespace App\Job;

use Exception;
use App\Utility\Uuid;

class JobManager
{
    public function __construct(private JobDir $jobDir, private JobStarter $jobStarter)
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
        $this->jobStarter->runAllJobsAsync();
        return $uuid;
    }

    public function getJobStatus(string $uuid, bool $cleanup = true): array
    {
        $job = $this->jobDir->job($uuid);
        $res = ["finished" => $job->isFinished()];
        if ($res['finished']) {
            $res['result'] = $job->getResult($cleanup);
        }
        return $res;
    }
}
