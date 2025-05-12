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

    public function listJobs(): array
    {
        $dd = @opendir($this->jobDir);
        $jobs = [];
        if ($dd) {
            while (($file = readdir($dd)) !== false) {
                if ($file === "." || $file === "..") {
                    continue;
                }
                if (preg_match('/^(.*)\.job\.yaml$/', $file, $matches)) {
                    $jobs[] = $matches[1];
                }
            }
            closedir($dd);
        }
        return array_map(fn ($uuid) => $this->job($uuid), $jobs);
    }

    public function job(string $uuid): Job
    {
        return new Job($uuid, $this->getFilename($uuid));
    }

    private function getFilename(string $uuid): string
    {
        return sprintf("%s/%s.job.yaml", $this->jobDir, $uuid);
    }
}
