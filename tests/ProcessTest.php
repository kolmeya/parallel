<?php

use Kolmeya\Parallel\Process;
use Kolmeya\Parallel\Runnable;

// Definições de classes de suporte para os testes
class MyThread extends Process
{
    public function run(): void
    {
        for ($i = 0; $i < 3; $i++) {
            // echo "thread pid:" . getmypid() . PHP_EOL;
        }
    }
}

class MyRunnable implements Runnable
{
    public function run(): void
    {
        for ($i = 0; $i < 3; $i++) {
            // echo "runnable pid:" . getmypid() . PHP_EOL;
        }
    }
}

// Testes de Process
test('process failed with exit code', function () {
    $process = new Process(function () {
        exit(255);
    });
    $process->start();
    $process->wait();

    expect($process->errno())->toBe(255);
    expect($process->errmsg())->toBe("Unknown error: 255");
});

test('process can be shutdown', function () {
    $process = new Process(function () {
        sleep(3);
    });
    $time = time();
    $process->start();
    $process->shutdown(SIGKILL);

    expect($process->isRunning())->toBeFalse();
    expect(time() - $time)->toBeLessThan(3);
    expect($process->ifSignal())->toBeTrue();
    expect($process->errno())->toBe(0);
});

test('process can wait for completion', function () {
    // Thread
    $process_thread = new MyThread();
    $process_thread->start();
    $process_thread->wait();

    expect($process_thread->errno())->toBe(0);
    expect($process_thread->errmsg())->toBe('Undefined error: 0');
    expect($process_thread->isRunning())->toBeFalse();

    // Runnable
    $process_runnable = new Process(new MyRunnable());
    $process_runnable->start();
    $process_runnable->wait();

    expect($process_runnable->errno())->toBe(0);

    // Callback
    $process_callback = new Process(function () {
        for ($i = 0; $i < 3; $i++) {
            // echo "callback pid:" . getmypid() . PHP_EOL;
        }
    });
    $process_callback->start();
    $process_callback->wait();

    expect($process_callback->errno())->toBe(0);
});
