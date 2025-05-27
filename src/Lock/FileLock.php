<?php

namespace Kolmeya\Parallel\Lock;

class FileLock implements LockInterface
{
    protected string $file;

    /** @var resource|false */
    protected $fileResource;

    protected bool $locked = false;

    private function __construct(string $file)
    {
        if (!file_exists($file) || !is_readable($file)) {
            throw new \RuntimeException("{$file} is not exists or not readable");
        }

        $this->fileResource = fopen($file, 'rb+');

        if (empty($this->fileResource)) {
            throw new \RuntimeException("open {$file} failed");
        }
    }

    public static function create(string $file): FileLock
    {
        return new FileLock($file);
    }

    public function acquire(bool $blocking = true): bool
    {
        if ($this->locked) {
            throw new \RuntimeException('already lock by yourself');
        }

        $locked = $blocking ? flock($this->fileResource, LOCK_EX) : flock($this->fileResource, LOCK_EX | LOCK_NB);

        if ($locked !== true) {
            return false;
        }

        $this->locked = true;

        return true;
    }

    public function isLocked(): bool
    {
        return $this->locked === true ? true : false;
    }

    public function __destruct()
    {
        if ($this->locked) {
            $this->release();
        }
    }

    public function release(): bool
    {
        if (!$this->locked) {
            throw new \RuntimeException('release a non lock');
        }

        $unlock = flock($this->fileResource, LOCK_UN);

        fclose($this->fileResource);

        if ($unlock !== true) {
            return false;
        }

        $this->locked = false;

        return true;
    }
}
