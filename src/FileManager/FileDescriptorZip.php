<?php

namespace App\FileManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\HeaderUtils;
use App\Utility\ByteCountFormatter;
use Exception;

class FileDescriptorZip extends FileDescriptor
{
    private string $filename;
    private int $byteCount;

    public function __construct(private string $zipFile, array $stat)
    {
        $this->filename = basename($stat['name']);
        $this->byteCount = $stat['size'];
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getPath(): string
    {
        throw new Exception("Path not available for zipped files");
    }

    public function getByteCount(): ?int
    {
        return $this->byteCount;
    }

    public function getDownloadResponse(): Response
    {
        $uri = $this->getFileUri();
        $response = new StreamedResponse(function () use ($uri) {
            readfile($uri);
        });
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $this->getFilename());
        $response->headers->set('Content-Disposition', $disposition);
        $size = $this->getByteCount();
        if ($size !== null) {
            $response->headers->set('Content-Length', (string)$size);
        }

        return $response;
    }

    private function getFileUri(): string
    {
        return sprintf("zip://%s#/%s", $this->zipFile, $this->filename);
    }
}
