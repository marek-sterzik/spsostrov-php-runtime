<?php

namespace App\Sync;

abstract class AbstractSyncService implements SyncServiceInterface
{
    abstract public static function getProtocols(): array;

    public function createSync(array $parsedUri): Sync
    {
        return new Sync($this, $this->createDriverData($parsedUri));
    }

    abstract protected function createDriverData(array $parsedUri): array;
    abstract public function syncFile(array $driverData, string $basePath, string $subdir, string $file): void;
    abstract public function isEnabled(array $driverData): bool;
    abstract public function testConnection(array $driverData): bool;
}

