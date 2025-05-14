<?php

namespace App\Sync;

class Sync
{
    public function __construct(private SyncServiceInterface $driver, private array $driverData)
    {
    }

    public function syncFile(string $basePath, string $subdir, string $file): void
    {
        $this->driver->syncFile($this->driverData, $basePath, $subdir, $file);
    }

    public function isEnabled(): bool
    {
        return $this->driver->isEnabled($this->driverData);
    }
}
