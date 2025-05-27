<?php

use Kolmeya\Parallel\Process;
use Kolmeya\Parallel\Pool;

test('pool methods', function () {
    $process = new Process(function () {
        sleep(3);
    }, 'test');
    
    $pool = new Pool();
    $pool->execute($process);
    
    expect($pool->aliveCount())->toBe(1);
    expect($pool->getProcessByPid($process->getPid()))->toBe($process);
    expect($pool->getProcessByName('test'))->toBe($process);
    
    $pool->shutdown();
});

test('pool alive count and wait', function () {
    $pool = new Pool();
    
    for ($i = 0; $i < 10; $i++) {
        $process = new Process(function () {
            sleep(3);
        });
        $pool->execute($process);
    }
    
    $start = time();
    expect($pool->aliveCount())->toBe(10);
    
    $pool->wait();
    $time = time() - $start;
    
    expect($time)->toBeGreaterThanOrEqual(3);
    expect($pool->aliveCount())->toBe(0);
});

test('pool shutdown', function () {
    $pool = new Pool();
    
    for ($i = 0; $i < 10; $i++) {
        $process = new Process(function () {
            sleep(3);
        });
        $pool->execute($process);
    }
    
    $start = time();
    $pool->shutdown();
    $time = time() - $start;
    
    expect($time)->toBeLessThan(3);
    expect($pool->aliveCount())->toBe(0);
});

test('pool shutdown force', function () {
    $pool = new Pool();
    
    for ($i = 0; $i < 10; $i++) {
        $process = new Process(function () {
            sleep(3);
        });
        $pool->execute($process);
    }
    
    $start = time();
    $pool->shutdownForce();
    $time = time() - $start;
    
    expect($time)->toBeLessThan(3);
    expect($pool->aliveCount())->toBe(0);
});
