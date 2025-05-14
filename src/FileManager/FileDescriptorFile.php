<?php

namespace App\FileManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Utility\ByteCountFormatter;

class FileDescriptorFile extends FileDescriptor
{
    private ?int $byteCount = null;

    public function __construct(private string $filename, private string $path)
    {
        $size = filesize($this->path);
        if ($size !== false) {
            $this->byteCount = $size;
        }
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getByteCount(): ?int
    {
        return $this->byteCount;
        
    }

    public function getDownloadResponse(): Response
    {
        $response = new BinaryFileResponse($this->getPath());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->getFilename());
        return $response;
    }
}
