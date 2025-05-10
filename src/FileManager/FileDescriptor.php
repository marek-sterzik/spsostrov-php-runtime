<?php

namespace App\FileManager;

use App\Utility\ByteCountFormatter;

class FileDescriptor
{
    public function __construct(private string $directory, private string $filename)
    {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getByteCount(): ?int
    {
        $size = filesize($this->directory . "/" . $this->filename);
        return ($size === false) ? null : $size;
    }

    public function getByteCountFormatted(): ?string
    {
        $byteCount = $this->getByteCount();
        return ($byteCount !== null) ? ByteCountFormatter::format($byteCount) : null;
    }
}
