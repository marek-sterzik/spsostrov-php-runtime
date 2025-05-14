<?php

namespace App\Sync;

use Exception;

class DummySync extends AbstractSyncService
{
    public static function getProtocols(): array
    {
        return ['dummy', 'none', 'disabled'];
    }

    protected function createDriverData(array $parsedUri): array
    {
        if ($parsedUri['is_uri']) {
            throw new Exception(sprintf("Invalid driver configuration for driver %s: %s", $protocol));
        }
        return ["enabled" => ($parsedUri['scheme'] === 'dummy') ? true : false];
    }

    public function syncFile(array $driverData, string $basePath, string $subdir, string $file): void
    {
    }

    public function isEnabled(array $driverData): bool
    {
        return $driverData['enabled'];
    }

    public function testConnection(array $driverData): bool
    {
        return true;
    }
}
