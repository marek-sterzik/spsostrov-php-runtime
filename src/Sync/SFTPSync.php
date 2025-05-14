<?php

namespace App\Sync;

use Exception;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\PublicKeyLoader;

class SFTPSync extends AbstractSyncService
{
    const DRIVER_FIELDS = [
        "host" => null,
        "port" => 22,
        "user" => null,
        "pass" => false,
        "path" => "/.",
        
    ];

    public static function getProtocols(): array
    {
        return ['sftp', 'ssh'];
    }

    public function __construct(private string $sshKeyFile)
    {
    }

    protected function createDriverData(array $parsedUri): array
    {
        $driverData = $this->doCreateDriverData($parsedUri);
        if ($driverData === null) {
            throw new Exception(sprintf("invalid sftp uri: %s", $parsedUri['uri']));
        }
        return $driverData;
    }

    private function doCreateDriverData(array $parsedUri): ?array
    {
        $driverData = [];
        if (!$parsedUri['is_uri']) {
            return null;
        }
        foreach (self::DRIVER_FIELDS as $field => $default) {
            if (isset($parsedUri[$field])) {
                $driverData[$field] = $parsedUri[$field];
            } elseif ($default !== null) {
                $driverData[$field] = $default;
            } else {
                return null;
            }
        }
        $driverData['user'] = urldecode($driverData['user']);
        $driverData['use_key'] = false;
        if ($driverData['pass'] !== false) {
            $driverData['pass'] = urldecode($driverData['pass']);
        } else {
            unset($driverData['pass']);
            $driverData['use_key'] = true;
        }
        $driverData['path'] = urldecode($driverData['path']);
        if (substr($driverData['path'], 0, 1) !== '/') {
            return null;
        }
        if (preg_match('|^/\.\.?(/.*)?$|', $driverData['path'])) {
            $driverData['path'] = substr($driverData['path'], 1);
            if (substr($driverData['path'], 0, 2) === './') {
                $driverData['path'] = substr($driverData['path'], 2);
            }
        }

        return $driverData;
    }

    public function syncFile(array $driverData, string $basePath, string $subdir, string $file): void
    {
    }

    public function isEnabled(array $driverData): bool
    {
        return true;
    }

    public function testConnection(array $driverData): bool
    {
        $sftp = $this->connect($driverData);
        var_dump($sftp->rawList());
        return true;
    }

    private function connect(array $driverData): SFTP
    {
        if ($driverData['use_key']) {
            $key = PublicKeyLoader::load(file_get_contents($this->sshKeyFile));
        } else {
            $key = $driverData['pass'];
        }

        $sftp = new SFTP($driverData['host'], $driverData['port']);
        if (!$sftp->login($driverData['user'], $key)) {
            throw new \Exception('Login failed');
        }

        if(!$sftp->chdir($driverData['path'])) {
            throw new \Exception('Cannot change the directory');
        }

        return $sftp;
    }
}
