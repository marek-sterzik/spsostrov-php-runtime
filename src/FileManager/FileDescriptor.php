<?php

namespace App\FileManager;

use App\Utility\ByteCountFormatter;

class FileDescriptor
{
    public function __construct(private string $filename, private string $path, private ?int $byteCount = null)
    {
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

    public function getByteCountFormatted(): ?string
    {
        $byteCount = $this->getByteCount();
        return ($byteCount !== null) ? ByteCountFormatter::format($byteCount) : null;
    }
}
