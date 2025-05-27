<?php

use Kolmeya\Parallel\Process;
use Kolmeya\Parallel\PoolFactory;

class FixedPoolTestProcess extends Process
{
    public function run(): void
    {
        sleep(3);
    }
}

test('fixed pool maintains maximum alive count', function () {
    $pool = PoolFactory::newFixedPool(4);

    // Adicionar 8 processos ao pool
    for ($i = 0; $i < 8; $i++) {
        $pool->execute(new FixedPoolTestProcess());
    }

    // Apenas 4 processos devem estar ativos (máximo configurado)
    expect($pool->aliveCount())->toBe(4);

    // Após os processos terminarem, outros devem iniciar automaticamente
    sleep(4);
    $pool->wait();
    expect($pool->aliveCount())->toBe(4);

    // Aguardar todos os processos finalizarem
    $pool->wait(true);
    expect($pool->aliveCount())->toBe(0);
});
