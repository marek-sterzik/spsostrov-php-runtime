<?php

namespace App\FileManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Utility\ByteCountFormatter;

abstract class FileDescriptor
{
    abstract public function getFilename(): string;
    abstract public function getPath(): string;
    abstract public function getByteCount(): ?int;
    abstract public function getDownloadResponse(): Response;

    public function getByteCountFormatted(): ?string
    {
        $byteCount = $this->getByteCount();
        return ($byteCount !== null) ? ByteCountFormatter::format($byteCount) : null;
    }

}
