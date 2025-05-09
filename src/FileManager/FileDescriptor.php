<?php

namespace App\FileManager;

class FileDescriptor
{
    public function __construct(private string $filename)
    {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }
}
