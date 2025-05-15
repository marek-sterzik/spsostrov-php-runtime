<?php

namespace App\Sync;

use Exception;

class Sync
{
    public function __construct(private SyncServiceInterface $driver, private array $driverData)
    {
    }

    public function syncFile(string $basePath, string $subdir, string $file): void
    {
        $this->driver->syncFile($this->driverData, $basePath, $subdir, $file);
    }

    public function removeFile(string $basePath, string $subdir, string $file): void
    {
        $this->driver->removeFile($this->driverData, $basePath, $subdir, $file);
    }

    public function isEnabled(): bool
    {
        return $this->driver->isEnabled($this->driverData);
    }

    public function isForceEnabled(): bool
    {
        return $this->isEnabled() && $this->driver->isForceEnabled($this->driverData);
    }

    public function testConnection(): bool
    {
        try {
            return $this->driver->testConnection($this->driverData);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getConfig(): array
    {
        return array_merge($this->getDefaultConfig(), $this->driver->getConfig($this->driverData));
    }

    private function getDefaultConfig(): array
    {
        $class = get_class($this->driver);
        $driver = null;
        if (is_callable([$class, "getProtocols"])) {
            $protocols = $class::getProtocols();
            $driver = $protocols[0] ?? null;
        }

        return [
            "driver" => $driver ?? "unknown",
            "enabled" => $this->isEnabled(),
            "forceEnabled" => $this->isForceEnabled(),
        ];
    }
}
