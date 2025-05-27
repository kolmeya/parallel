<?php

namespace Kolmeya\Parallel;

class PoolFactory
{
    public static function newPool(): Pool
    {
        return new Pool();
    }

    public static function newFixedPool(int $max = 4): FixedPool
    {
        return new FixedPool($max);
    }

    public static function newParallelPool($callback, int $max = 4): ParallelPool
    {
        return new ParallelPool($callback, $max);
    }

    public static function newSinglePool(): SinglePool
    {
        return new SinglePool();
    }
}
