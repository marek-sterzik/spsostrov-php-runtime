<?php

namespace App\Sync;

use Exception;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SyncManager
{
    private ?array $protocolMap = null;
    /**
     * @param ServiceLocator<SyncServiceInterface> $syncServices
     */
    public function __construct(private ServiceLocator $syncServices)
    {
    }

    public function createSync(string $uri): Sync
    {
        $parsedUri = $this->parseUri($uri);
        if ($parsedUri === null) {
            throw new Exception(sprintf("invalid sync uri: %s", $uri));
        }
        $service = $this->getSyncServiceByProtocol($parsedUri['protocol']);
        if ($service === null) {
            throw new Exception(sprintf("unsupported protocol: %s", $parsedUri['protocol']));
        }
        return $service->createSync($parsedUri);
    }

    private function getSyncServiceByProtocol(string $protocol): ?SyncServiceInterface
    {
        $protocolMap = $this->getProtocolMap();
        if (isset($protocolMap[$protocol])) {
            return $protocolMap[$protocol];
        }
        return null;
    }

    private function parseUri(string $uri): ?array
    {
        if (preg_match('/^[a-zA-Z0-9_]+(-[a-zA-Z0-9_]+)*$/', $uri)) {
            return ["protocol" => $uri, "is_uri" => false, "uri" => $uri];
        }
        $uriParsed = parse_url($uri);
        if (!is_array($uriParsed) || !isset($uriParsed['protocol'])) {
            return null;
        }
        $uriParsed['is_uri'] = true;
        $uriParsed['uri'] = $uri;
        return $uriParsed;
    }

    private function getProtocolMap(): array
    {
        if ($this->protocolMap === null) {
            $this->protocolMap = [];
            foreach ($this->syncServices as $service) {
                $class = get_class($service);
                foreach ($class::getProtocols() as $protocol) {
                    $this->protocolMap[$protocol] = $service;
                }
            }
        }
        return $this->protocolMap;
    }
}
