<?php

namespace Kolmeya\Parallel;

class SinglePool extends FixedPool
{
    public function __construct()
    {
        parent::__construct(1);
    }
}
