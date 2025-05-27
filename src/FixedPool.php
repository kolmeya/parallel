<?php

namespace Kolmeya\Parallel;

class FixedPool extends AbstractPool
{
    protected int $max;

    public function __construct(int $max = 4)
    {
        $this->max = $max;
    }

    public function execute(Process $process): void
    {
        Utils::checkOverwriteRunMethod(get_class($process));

        if ($this->aliveCount() < $this->max && !$process->isStarted()) {
            $process->start();
        }

        $this->processes[] = $process;
    }

    public function wait(bool $block = false, int $interval = 100): void
    {
        do {
            if ($this->isFinished()) {
                return;
            }
            parent::wait(false);
            if ($this->aliveCount() < $this->max) {
                foreach ($this->processes as $process) {
                    if ($process->isStarted()) {
                        continue;
                    }
                    $process->start();
                    if ($this->aliveCount() >= $this->max) {
                        break;
                    }
                }
            }
            $this->sleepIfBlocking($block, $interval);
        } while ($block);
    }
}
