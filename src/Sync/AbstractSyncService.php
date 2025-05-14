<?php

namespace App\Sync;

abstract class AbstractSyncService implements SyncServiceInterface
{
    abstract public static function getProtocols(): array;

    public function createSync(array $parsedUri): Sync
    {
        $driverData = $this->createDriverData($parsedUri);
        return new Sync(function ($basePath, $subdir, $file) use ($driverData) {
            $this->syncFile($driverData, $basePath, $subdir, $file);
        });
    }

    abstract protected function createDriverData(array $parsedUri): array;
    abstract protected function syncFile(array $driverData, string $basePath, string $subdir, string $file): void;
}

