<?php

namespace App\Job\Jobs;

class RemoveSubmission extends AbstractJob
{
    public static function getName(): string
    {
        return 'remove_submission';
    }

    public function run(array $arguments): void
    {
        var_dump($arguments);
    }
}
