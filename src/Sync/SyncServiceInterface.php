<?php

namespace App\Sync;

interface SyncServiceInterface
{
    public static function getProtocols(): array;
    public function createSync(array $parsedUri): Sync;
    public function syncFile(array $driverData, string $basePath, string $subdir, string $file): void;
    public function isEnabled(array $driverData): bool;
}
