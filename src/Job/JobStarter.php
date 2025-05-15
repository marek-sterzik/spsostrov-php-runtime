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

    public function runAllJobsAsync(): self
    {
        $command = escapeshellcmd('nohup') . " " . escapeshellarg($this->consoleCommand) . " " .
            escapeshellarg(self::COMMAND);
        system($command . " > /dev/null 2>&1 &");
        return $this;
    }

    public function runAllJobs(): self
    {
        foreach ($this->jobDir->listJobs(false) as $job) {
            if (!$job->isFinished()) {
                if (!$job->isRunning()) {
                    $this->invokeConsoleCommandForJob($job);
                }
            } else {
                if ($job->shouldBeCleanedUp()) {
                    $job->cleanup();
                }
            }
        }
        return $this;
    }

    private function invokeConsoleCommandForJob(Job $job): void
    {
        $command = escapeshellcmd('nohup') . " " . escapeshellarg($this->consoleCommand) . " " .
            escapeshellarg(self::COMMAND) . " "  . escapeshellarg($job->uuid());
        system($command . " > /dev/null 2>&1 &");
    }

    public function runJob(string $uuid): self
    {
        $job = $this->jobDir->job($uuid);
        $data = $job->tryStart();
        $ret = null;
        if (isset($data['job']) && is_string($data['job'])) {
            if (!isset($data['arguments'])) {
                $data['arguments'] = [];
            }
            if (is_array($data['arguments'])) {
                $ret = $this->runJobRaw($data['job'], $data['arguments']);
            }
        }
        $job->finish($ret);
        return $this;
    }

    private function runJobRaw(string $job, array $arguments): ?array
    {
        $job = $this->jobs->get($job);
        return $job->realRun($arguments);
    }
}
