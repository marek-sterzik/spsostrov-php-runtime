<?php

namespace App\Sync;

class SFTPSync extends AbstractSyncService
{
    public static function getProtocols(): array
    {
        return ['dummy', 'none'];
    }

    protected function createDriverData(array $parsedUri): array
    {
        if ($parsedUri['is_uri']) {
            throw new Exception("Invalid driver configuration for driver %s:");
        }
        return [];
    }

    protected function syncFile(array $driverData, string $basePath, string $subdir, string $file): void
    {
    }
}
