<?php

namespace App\Sync;

use Exception;

class DummySync extends AbstractSyncService
{
    public static function getProtocols(): array
    {
        return ['dummy', 'none'];
    }

    protected function createDriverData(array $parsedUri): array
    {
        if ($parsedUri['is_uri']) {
            throw new Exception(sprintf("Invalid driver configuration for driver %s: %s", $protocol));
        }
        return [];
    }

    protected function syncFile(array $driverData, string $basePath, string $subdir, string $file): void
    {
    }
}
