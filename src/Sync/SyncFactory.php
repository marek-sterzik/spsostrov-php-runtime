<?php

namespace App\Sync;

use Exception;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Psr\Log\LoggerInterface as Logger;

class SyncFactory
{
    public function __construct(
        private SyncManager $syncManager,
        private Logger $logger,
        private string $syncUri
    ) {
    }

    public function create(): Sync
    {
        try {
            return $this->syncManager->createSync($this->syncUri);
        } catch (Exception $e) {
            $this->logger->error(sprintf("Invalid sync uri, falling back to none: %s", $this->syncUri));
            return $this->syncManager->createSync("none");
        }
    }
}
