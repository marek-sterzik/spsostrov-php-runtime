<?php

namespace App\Framework;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

class VersionStrategy implements VersionStrategyInterface
{
    /** @var string|null */
    private $version;

    public function __construct(string $assetBaseDir, string $versionLinkWildCard)
    {
        $this->version = $this->detectVersion($assetBaseDir, $versionLinkWildCard);
    }

    public function getVersion(string $path): string
    {
        return $this->version;
    }

    public function applyVersion(string $path): string
    {
        if ($this->version !== null) {
            return sprintf("%s/%s", $this->version, $path);
        } else {
            return $path;
        }
    }

    private function detectVersion(string $assetBaseDir, string $versionWildCard): ?string
    {
        $cwd = getcwd();
        chdir($assetBaseDir);
        $versions = [];
        foreach (glob($versionWildCard) as $version) {
            if (is_dir($version)) {
                $versions[] = $version;
            }
        }
        chdir($cwd);

        if (empty($versions)) {
            return null;
        }

        sort($versions);

        return array_pop($versions);
    }
}
