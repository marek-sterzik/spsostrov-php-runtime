<?php

namespace App\Job;

use Exception;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ServiceLocator;
use App\Utility\Uuid;
use App\Job\Jobs\AbstractJob;

class JobStarter
{
    const COMMAND = "job:run";

    /**
     * @param ServiceLocator<AbstractJob> $jobs
     */
    public function __construct(private JobDir $jobDir, private ServiceLocator $jobs, private string $consoleCommand)
    {
    }

    public function runAllJobs(): self
    {
        foreach ($this->jobDir->listJobs() as $job) {
            if (!$job->isRunning()) {
                $this->invokeConsoleCommandForJob($job);
            }
        }
        return $this;
    }

    private function invokeConsoleCommandForJob(Job $job): void
    {
        $command = escapeshellcmd('nohup') . " " . escapeshellarg($this->consoleCommand) . " " .
            escapeshellarg("job:run") . " "  . escapeshellarg($job->uuid());
        system($command . " > /dev/null 2>&1 &");
    }

    public function runJob(string $uuid): self
    {
        $job = $this->jobDir->job($uuid);
        $data = $job->tryStart();
        if (isset($data['job']) && is_string($data['job'])) {
            if (!isset($data['arguments'])) {
                $data['arguments'] = [];
            }
            if (is_array($data['arguments'])) {
                $this->runJobRaw($data['job'], $data['arguments']);
            }
        }
        $job->finish();
        return $this;
    }

    private function runJobRaw(string $job, array $arguments): self
    {
        $job = $this->jobs->get($job);
        $job->run($arguments);
        return $this;
    }
}
