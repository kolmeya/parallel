<?php

use Kolmeya\Parallel\Lock\FileLock;
use Kolmeya\Parallel\Process;

beforeEach(function () {
    $this->lock = FileLock::create('./kolmeya-parallel.lock');
});

afterEach(function () {
    unset($this->lock);
});

test('file lock can be acquired and released', function () {
    expect($this->lock->acquire())->toBeTrue();
    expect($this->lock->release())->toBeTrue();
});

test('file lock throws exception when acquiring twice', function () {
    $this->lock->acquire();

    expect(fn() => $this->lock->acquire())->toThrow(RuntimeException::class);
});

test('file lock throws exception when releasing without acquiring', function () {
    expect(fn() => $this->lock->release())->toThrow(RuntimeException::class);
});

test('file lock communication between processes', function () {
    $lock_file = "./kolmeya-parallel.lock";
    if (!file_exists($lock_file)) {
        touch($lock_file);
    }

    $process = new Process(function () use ($lock_file) {
        $lock = FileLock::create($lock_file);
        $lock->acquire(false);
        sleep(5);
        $lock->release();
    });

    $process->start();
    sleep(3);

    $lock = FileLock::create($lock_file);
    expect($lock->acquire(false))->toBeFalse();

    $process->wait();
    expect($lock->acquire(false))->toBeTrue();
    expect($lock->release())->toBeTrue();
});
