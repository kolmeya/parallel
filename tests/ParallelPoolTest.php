<?php

use Kolmeya\Parallel\ParallelPool;
use Kolmeya\Parallel\Runnable;

// Classe de teste para o ParallelPool
class ParallelPoolTestRunnable implements Runnable
{
    public function run(): void
    {
        sleep(3);
    }
}

test('parallel pool lifecycle', function () {
    $pool = new ParallelPool(new ParallelPoolTestRunnable(), 10);
    $pool->start();

    expect($pool->aliveCount())->toBe(10);

    sleep(4);
    expect($pool->aliveCount())->toBe(0);

    $pool->keep();
    expect($pool->count())->toBe(10);
    expect($pool->aliveCount())->toBe(10);

    $pool->wait(true);
});

test('parallel pool throws exception with invalid runnable', function () {
    expect(fn() => new ParallelPool('parallel'))->toThrow(InvalidArgumentException::class);
});

test('parallel pool can reload processes', function () {
    $pool = new ParallelPool(new ParallelPoolTestRunnable(), 10);
    $pool->start();

    expect($pool->aliveCount())->toBe(10);

    $old_processes = $pool->getProcesses();
    $pool->reload();
    $new_processes = $pool->getProcesses();

    // Verificar se todos os processos antigos são diferentes dos novos
    foreach ($old_processes as $old_process) {
        foreach ($new_processes as $new_process) {
            expect($old_process->getPid())->not->toBe($new_process->getPid());
        }
    }

    $pool->shutdown();
});
