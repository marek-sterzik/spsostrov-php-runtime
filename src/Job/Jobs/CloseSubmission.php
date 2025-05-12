<?php

namespace App\Job\Jobs;

class CloseSubmission extends AbstractJob
{
    public static function getName(): string
    {
        return 'close_submission';
    }

    public function run(array $arguments): void
    {
        var_dump($arguments);
    }
}
