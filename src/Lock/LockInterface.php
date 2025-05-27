<?php

namespace Kolmeya\Parallel\Lock;

interface LockInterface
{
    public function acquire(bool $blocking = true): bool;
    public function release(): bool;
    public function isLocked(): bool;
}
