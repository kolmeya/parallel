<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Função utilitária para aguardar que uma condição seja verdadeira
 * Útil para testes que precisam esperar por operações assíncronas.
 *
 * @param callable $condition A condição a ser verificada
 * @param int $timeout Tempo limite em segundos
 * @param int $checkInterval Intervalo entre verificações em microssegundos
 * @return bool true se a condição se tornou verdadeira, false se atingiu o timeout
 */
function waitFor(callable $condition, int $timeout = 5, int $checkInterval = 100000): bool
{
    $start = time();
    while (time() - $start < $timeout) {
        if ($condition()) {
            return true;
        }
        usleep($checkInterval);
    }
    return false;
}

/**
 * Cria um arquivo temporário com o conteúdo especificado
 *
 * @param string $content Conteúdo do arquivo
 * @param string $prefix Prefixo para o nome do arquivo
 * @return string Caminho do arquivo temporário
 */
function createTempFile(string $content = '', string $prefix = 'test_'): string
{
    $tempFile = tempnam(sys_get_temp_dir(), $prefix);
    if ($content) {
        file_put_contents($tempFile, $content);
    }
    return $tempFile;
}
