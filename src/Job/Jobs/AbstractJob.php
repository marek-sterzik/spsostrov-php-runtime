<?php

namespace App\Job\Jobs;

abstract class AbstractJob
{
    abstract public static function getName(): string;
    abstract public function run(array $arguments): ?array;

    public function realRun(array $arguments): ?array
    {
        return $this->run($arguments);
    }
}
