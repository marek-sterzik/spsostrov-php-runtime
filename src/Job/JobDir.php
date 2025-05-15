<?php

namespace App\Job;

use Exception;

class JobDir
{
    public function __construct(private string $jobDir)
    {
        if (!is_dir($jobDir)) {
            @mkdir($jobDir, 0755, true);
            if (!is_dir($jobDir)) {
                throw new Exception(sprintf("Cannot create job dir: %s", $jobDir));
            }
        }
    }

    public function listJobs(bool $runningOnly = false): array
    {
        $dd = @opendir($this->jobDir);
        $jobs = [];
        if ($dd) {
            while (($file = readdir($dd)) !== false) {
                if ($file === "." || $file === "..") {
                    continue;
                }
                $regexp = '/^(.*)\.(' . ($runningOnly ? 'job' : 'job|result') . ')\.yaml$/';
                if (preg_match($regexp, $file, $matches)) {
                    $jobs[$matches[1]] = true;
                }
            }
            closedir($dd);
        }
        return array_map(fn ($uuid) => $this->job($uuid), array_keys($jobs));
    }

    public function job(string $uuid): Job
    {
        return new Job($uuid, $this->getFilename($uuid), $this->getResultFilename($uuid));
    }

    private function getFilename(string $uuid): string
    {
        return sprintf("%s/%s.job.yaml", $this->jobDir, $uuid);
    }

    private function getResultFilename(string $uuid): string
    {
        return sprintf("%s/%s.result.yaml", $this->jobDir, $uuid);
    }
}
