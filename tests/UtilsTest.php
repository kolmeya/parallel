<?php

use Kolmeya\Parallel\Utils;
use Kolmeya\Parallel\Process;

// Classe de teste para Utils
class UtilsTestProcess extends Process
{
    public function run(): void
    {
        echo 'run' . PHP_EOL;
    }
}

test('utils check overwrite run method success', function () {
    $process = new UtilsTestProcess();

    // Não deve lançar exceção
    expect(fn() => Utils::checkOverwriteRunMethod(get_class($process)))->toBeObject();
});

test('utils check overwrite run method error', function () {
    // Deve lançar exceção
    expect(fn() => Utils::checkOverwriteRunMethod(get_class(new Process())))->toThrow(RuntimeException::class);
});
