<?php

namespace App\Job;

use Exception;
use App\Utility\Uuid;
use Symfony\Component\Yaml\Yaml;

class JobManager
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

    public function invoke(string $job, array $arguments = []): string
    {
        $uuid = Uuid::uuid6()->toString();
        $data = [
            "job" => $job,
            "arguments" => $arguments,
        ];
        $data = Yaml::dump($data, 3) . "\n";
        $filename = sprintf("%s/%s.job.yaml", $this->jobDir, $uuid);
        file_put_contents($filename, $data);
        return $uuid;
    }
}
