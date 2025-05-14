<?php

namespace App\FileManager;

use Exception;
use ZipArchive;
use App\Submission\SubmissionState;
use App\Entity\Submission;

class ZipOperations
{
    public function __construct(private FileOperations $fileOperations)
    {
    }

    public function pack(Submission $submission): self
    {
        $tmpZip = $this->fileOperations->getSubmissionZipArchive($submission, true);
        $finalZip = $this->fileOperations->getSubmissionZipArchive($submission, false);
        @unlink($tmpZip);
        $zip = new ZipArchive();
        if (!$zip->open($tmpZip, ZipArchive::CREATE)) {
            throw new Exception("Cannot open zip archive");
        }
        $found = false;
        foreach ($this->fileOperations->listFiles($submission, true) as $file) {
            $found = true;
            $zip->addFile($file->getPath(), "/" . $file->getFilename());
        }
        $zip->close();
        if ($found) {
            @unlink($finalZip);
            @rename($tmpZip, $finalZip);
        }
        if (!is_file($finalZip)) {
            throw new Exception("Zip archive cannot be created");
        }

        $this->fileOperations->cleanup($submission);
        return $this;
    }

    public function listFiles(Submission $submission): ?array
    {
        $zipFile = $this->fileOperations->getSubmissionZipArchive($submission, false);
        if (!is_file($zipFile)) {
            return null;
        }
        $zip = new ZipArchive();

        $zip->open($zipFile, ZipArchive::RDONLY);

        $files = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat['name'] !== '/' . FileOperations::MANIFEST_NAME) {
                $files[] = new FileDescriptorZip($zipFile, $stat);
            }
        }
        $zip->close();
        return $files;
    }

    public function getSingleFile(Submission $submission, string $filename): ?FileDescriptor
    {
        $filename = $this->fileOperations->canonizeFilename($filename);
        if ($filename === null) {
            return null;
        }

        $zipFile = $this->fileOperations->getSubmissionZipArchive($submission, false);
        if (!is_file($zipFile)) {
            return null;
        }

        $zip = new ZipArchive();

        $zip->open($zipFile, ZipArchive::RDONLY);

        $stat = $zip->statName("/" . $filename);

        $zip->close();

        if ($stat === false) {
            return null;
        }

        return new FileDescriptorZip($zipFile, $stat);
    }
}
