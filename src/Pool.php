<?php

namespace Kolmeya\Parallel;

class Pool extends AbstractPool
{
    public function execute(Process $process, ?string $processName = null): Process
    {
        if ($processName !== null) {
            $process->name($processName);
        }

        if (!$process->isStarted()) {
            $process->start();
        }

        $this->processes[] = $process;

        return $process;
    }
}
