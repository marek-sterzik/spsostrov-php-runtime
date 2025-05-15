<?php

namespace App\Job;

use Exception;
use Symfony\Component\Yaml\Yaml;

class Job
{
    private mixed $fd = null;

    public function __construct(private string $uuid, private string $jobFile, private string $resultFile)
    {
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function create(array $data): self
    {
        $data = Yaml::dump($data, 3) . "\n";
        file_put_contents($this->jobFile, $data, LOCK_EX);
        @unlink($this->resultFile);
        return $this;
    }

    public function isFinished(): bool
    {
        return !is_file($this->jobFile);
    }

    public function getResult(bool $cleanup = true): ?array
    {
        if (!$this->isFinished()) {
            throw new Exception("Cannot ask for result of a non-finished job");
        }
        $res = @file_get_contents($this->resultFile);
        if (!is_string($res)) {
            return null;
        }
        try {
            $res = Yaml::parse($res);
        } catch (Exception $e) {
            $res = null;
        }
        if ($cleanup) {
            @unlink($this->resultFile);
        }
        return $res;
    }

    public function shouldBeCleanedUp(): bool
    {
        $ctime = @filectime($this->resultFile);
        if ($ctime === false || $ctime < time() - 24 * 3600) {
            return true;
        }
        return false;
    }

    public function cleanup(): self
    {
        @unlink($this->jobFile);
        @unlink($this->resultFile);
        return $this;
    }

    public function isRunning(): bool
    {
        $fd = @fopen($this->jobFile, "r+");
        if (!$fd) {
            return false;
        }
        if (flock($fd, LOCK_EX|LOCK_NB)) {
            $ret = false;
            flock($fd, LOCK_UN);
        } else {
            $ret = true;
        }
        fclose($fd);
        return $ret;
    }

    public function tryStart(): ?array
    {
        $this->fd = @fopen($this->jobFile, "r+");
        if (!$this->fd) {
            $this->fd = null;
            return null;
        }
        if (!flock($this->fd, LOCK_EX|LOCK_NB)) {
            fclose($this->fd);
            $this->fd = null;
            return null;
        }
        $data = stream_get_contents($this->fd);
        if (!is_string($data)) {
            flock($this->fd, LOCK_UN);
            fclose($this->fd);
            $this->fd = null;
            return null;
        }

        try {
            $data = Yaml::parse($data);
        } catch (Exception $e) {
            flock($this->fd, LOCK_UN);
            fclose($this->fd);
            $this->fd = null;
            return null;
        }

        if (!is_array($data)) {
            flock($this->fd, LOCK_UN);
            fclose($this->fd);
            $this->fd = null;
            return null;
        }

        return $data;
    }

    public function finish(?array $ret): self
    {
        if ($ret !== null) {
            file_put_contents($this->resultFile, Yaml::dump($ret, 3));
        } else {
            @unlink($this->resultFile);
        }

        if ($this->fd !== null) {
            fseek($this->fd, 0, SEEK_SET);
            ftruncate($this->fd, 0);
            flock($this->fd, LOCK_UN);
            fclose($this->fd);
            @unlink($this->jobFile);
            $this->fd = null;
        }
        return $this;
    }
}
