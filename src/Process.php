<?php

namespace Kolmeya\Parallel;

class Process
{
    /** @var callable|Runnable|null */
    protected $runnable;

    protected ?int $pid = 0;

    protected ?string $name = null;

    protected bool $started = false;

    protected bool $running = false;

    protected ?int $termSignal = null;

    protected ?int $stopSignal = null;

    protected ?int $errorCode = null;

    protected ?string $errorMessage = null;

    protected bool $terminateSignal = false;

    protected array $signalHandlers = [];

    public function run(): void
    {
    }

    public function __construct($execution = null, ?string $name = null)
    {
        $this->initRunnable($execution);
        $this->name = $name ?? $this->name;
        $this->initStatus();
    }

    public function setChildrenTerminateSignal(int $status): void
    {
        if (pcntl_wifsignaled($status)) {
            $this->termSignal = pcntl_wtermsig($status);
        }
    }

    public function setStopSignal(int $status): void
    {
        if (pcntl_wifstopped($status)) {
            $this->stopSignal = pcntl_wstopsig($status);
        }
    }

    public function setErrors(int $status): void
    {
        $this->errorCode = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : pcntl_get_last_error();
        $this->errorMessage = pcntl_wifexited($status) ? pcntl_strerror($this->errorCode) : pcntl_strerror($this->errorCode);
    }

    public function setTerminateSignal(int $status): void
    {
        if (pcntl_wifsignaled($status)) {
            $this->terminateSignal = true;
        } else {
            $this->terminateSignal = false;
        }
    }

    protected function initRunnable($execution): void
    {
        if ($execution === null) {
            Utils::checkOverwriteRunMethod(get_class($this));
            return;
        }

        if ($execution instanceof Runnable || is_callable($execution)) {
            $this->runnable = $execution;
            return;
        }

        throw new \InvalidArgumentException(
            'O parâmetro execution deve ser uma instância de Runnable ou uma função callable'
        );
    }

    protected function initStatus(): void
    {
        $this->pid = null;
        $this->running = false;
        $this->termSignal = null;
        $this->stopSignal = null;
        $this->errorCode = null;
        $this->errorMessage = null;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function name(?string $name = null)
    {
        if ($name === null) {
            return $this->name;
        }

        $this->name = $name;
        return $this;

    }

    public function isStopped(): bool
    {
        if (is_null($this->errorCode)) {
            return false;
        }

        return true;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function errno(): ?int
    {
        return $this->errorCode;
    }

    public function errmsg(): ?string
    {
        return $this->errorMessage;
    }

    public function ifSignal(): bool
    {
        return $this->terminateSignal;
    }

    public function start(): bool
    {
        if (!empty($this->pid) && $this->isRunning()) {
            throw new \LogicException("The process is already running");
        }

        $callback = $this->getCallable();

        $pid = pcntl_fork();

        if ($pid < 0) {
            throw new \RuntimeException('Fork error: '.pcntl_strerror(pcntl_get_last_error()));
        }

        // Main process
        if ($pid > 0) {
            $this->pid = $pid;
            $this->running = true;
            $this->started = true;

            return true;
        }

        // Children process
        try {
            $this->pid = getmypid();

            $this->signal();
            foreach ($this->signalHandlers as $signal => $handler) {
                pcntl_signal($signal, $handler);
            }

            call_user_func($callback);

            exit(0);
        } catch (\Throwable $e) {
            error_log('Error in the child process: '.$e->getMessage());
            exit(1);
        }

    }

    public function isRunning(): bool
    {
        $this->updateStatus();
        return $this->running;
    }

    protected function updateStatus($block = false): void
    {
        if ($this->running !== true) {
            return;
        }

        if ($block) {
            $res = pcntl_waitpid($this->pid, $status);
        } else {
            $res = pcntl_waitpid($this->pid, $status, WNOHANG | WUNTRACED);
        }

        if ($res === -1) {
            throw new \RuntimeException('pcntl_waitpid failed. the process maybe available');
        }

        if ($res === 0) {
            $this->running = true;
        } else {
            $this->setChildrenTerminateSignal($status);
            $this->setStopSignal($status);
            $this->setErrors($status);
            $this->setTerminateSignal($status);

            $this->running = false;
        }
    }

    protected function getCallable(): callable
    {
        $callback = [$this, 'run'];

        if (is_object($this->runnable) && $this->runnable instanceof Runnable) {
            $callback = [$this->runnable, 'run'];
        }

        if (is_callable($this->runnable)) {
            $callback = $this->runnable;
        }

        return $callback;
    }

    protected function signal(): void
    {
        pcntl_signal(SIGTERM, function () {
            exit(0);
        });
    }

    public function shutdown(bool $block = true, int $signal = SIGTERM): void
    {
        if (empty($this->pid)) {
            throw new \LogicException('the process pid is null, so maybe the process is not started');
        }

        if (!$this->isRunning()) {
            throw new \LogicException("the process is not running");
        }

        if (!posix_kill($this->pid, $signal)) {
            throw new \RuntimeException("kill son process failed");
        }

        $this->updateStatus($block);
    }

    public function wait(bool $block = true, int $sleep = 100000): void
    {
        while (true) {
            if ($this->isRunning() === false) {
                return;
            }
            if (!$block) {
                break;
            }
            usleep($sleep);
        }
    }

    public function registerSignalHandler(int $signal, callable $handler): void
    {
        $this->signalHandlers[$signal] = $handler;
    }

    public function dispatchSignal(): bool
    {
        return pcntl_signal_dispatch();
    }
}
