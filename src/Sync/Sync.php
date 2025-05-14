<?php

namespace App\Sync;

class Sync
{
    private mixed $syncFunction;

    public function __construct(callable $syncFunction)
    {
        $this->syncFunction = $syncFunction;
    }

    public function syncFile(string $basePath, string $subdir, string $file): void
    {
        $syncFunction = $this->syncFunction;
        $syncFunction($basePath, $subdir, $file);
    }

}
