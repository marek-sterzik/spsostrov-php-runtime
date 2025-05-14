<?php

namespace App\Sync;

interface SyncServiceInterface
{
    public static function getProtocols(): array;
    public function createSync(array $parsedUri): Sync;
}
