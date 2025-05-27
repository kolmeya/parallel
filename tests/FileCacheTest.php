<?php

use Kolmeya\Parallel\Cache\FileCache;

// Dados para os testes
$cacheTestData = [
    ["/tmp/cache1", "key1", "value1"],
    ["/tmp/cache2", "key2", "value2"],
    ["/tmp/cache3", "key3", "value3"],
    ["/tmp/cache4", "key4", "value4"]
];

// Testes de FileCache
test('file cache operations', function ($path, $key, $value) {
    $cache = new FileCache($path);
    
    expect(file_exists($path))->toBeTrue();
    expect($cache->set($key, $value, 2))->toBeTrue();
    expect($cache->get($key))->toBe($value);
    
    sleep(4);
    expect($cache->get($key))->toBeNull();
    
    expect($cache->set($key, $value))->toBeTrue();
    expect($cache->delete($key))->toBeTrue();
    expect($cache->get($key))->toBeNull();
    
    expect($cache->flush())->toBeTrue();
    expect(file_exists($path))->toBeFalse();
})->with($cacheTestData);
