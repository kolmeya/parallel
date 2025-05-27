<?php

namespace Kolmeya\Parallel;

class ParallelPool extends AbstractPool
{
    /** @var callable|Runnable */
    protected $runnable;

    protected int $max;

    public function __construct($callback, int $max = 4)
    {
        if (!is_callable($callback) && !($callback instanceof Runnable)) {
            throw new \InvalidArgumentException('Callback must be a function or a object of Runnable');
        }

        $this->runnable = $callback;
        $this->max = $max;
    }

    public function reload(bool $block = true): void
    {
        $oldProcesses = $this->processes;
        $this->createProcesses($this->max);
        $this->shutdownOldProcesses($oldProcesses, $block);
    }

    private function shutdownOldProcesses(array $oldProcesses, bool $block): void
    {
        foreach ($oldProcesses as $process) {
            $process->shutdown();
            $process->wait($block);
            unset($this->processes[$process->getPid()]);
        }
    }

    public function keep(bool $block = false, int $interval = 100): void
    {
        do {
            $this->maintainPoolSize();
            $this->removeInactiveProcesses();
            $this->sleepIfBlocking($block, $interval);
        } while ($block);
    }

    private function maintainPoolSize(): void
    {
        $this->start();
    }

    public function start(): void
    {
        $aliveCount = $this->aliveCount();
        $neededProcesses = $this->max - $aliveCount;

        if ($neededProcesses > 0) {
            $this->createProcesses($neededProcesses);
        }
    }

    private function createProcesses(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $process = new Process($this->runnable);
            $process->start();
            $this->processes[$process->getPid()] = $process;
        }
    }
}
