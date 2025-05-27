<?php

namespace Kolmeya\Parallel;

abstract class AbstractPool
{
    protected array $processes = [];

    public function getProcessByPid(int $pid): ?Process
    {
        foreach ($this->processes as $process) {
            if ($process->getPid() == $pid) {
                return $process;
            }
        }

        return null;
    }

    public function shutdownForce(): void
    {
        $this->shutdown(SIGKILL);
    }

    public function shutdown(int $signal = SIGTERM): void
    {
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $process->shutdown(true, $signal);
            }
        }
    }

    public function isFinished(): bool
    {
        foreach ($this->processes as $process) {
            if (!$process->isStopped()) {
                return false;
            }
        }
        return true;
    }

    public function wait(bool $block = true, int $sleep = 100): void
    {
        do {
            usleep($sleep);
        } while ($block && $this->aliveCount() > 0);
    }

    public function aliveCount(): int
    {
        $count = 0;
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $count++;
            }
        }

        return $count;
    }

    protected function sleepIfBlocking(bool $isBlockingMode, int $microseconds): void
    {
        if ($isBlockingMode) {
            usleep($microseconds);
        }
    }

    public function getProcessByName(string $name): ?Process
    {
        foreach ($this->processes as $process) {
            if ($process->name() == $name) {
                return $process;
            }
        }

        return null;
    }

    public function removeProcessByName(string $name): void
    {
        foreach ($this->processes as $key => $process) {
            if ($process->name() == $name) {
                if ($process->isRunning()) {
                    throw new \RuntimeException("can not remove a running process");
                }
                unset($this->processes[$key]);
            }
        }
    }

    public function removeExitedProcess(): void
    {
        foreach ($this->processes as $key => $process) {
            if ($process->isStopped()) {
                unset($this->processes[$key]);
            }
        }
    }

    protected function removeInactiveProcesses(): void
    {
        foreach ($this->processes as $process) {
            if (!$process->isRunning()) {
                unset($this->processes[$process->getPid()]);
            }
        }
    }

    public function count(): int
    {
        return count($this->processes);
    }

    public function getProcesses(): array
    {
        return $this->processes;
    }
}
