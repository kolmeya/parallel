<?php

namespace Kolmeya\Parallel\Cache;

class FileCache implements CacheInterface
{
    private $cachePath;

    public function __construct($cachePath)
    {
        $this->cachePath = $cachePath;
        if (!is_dir($cachePath)) {
            $makeDirResult = mkdir($cachePath, 0755, true);
            if ($makeDirResult === false) {
                throw new \Exception('Cannot create the cache directory');
            }
        }
    }


    public function get($key, $default = null)
    {
        $cacheData = $this->getItem($key);
        if ($cacheData === false || !is_array($cacheData)) {
            return $default;
        }

        return $cacheData['data'];
    }

    public function set($key, $value, $expire = 0): bool
    {
        return $this->setItem($key, $value, time(), $expire);
    }

    private function setItem($key, $value, $time, $expire)
    {
        $cacheFile = $this->createCacheFile($key);
        if ($cacheFile === false) {
            return false;
        }

        $cacheData = array('data' => $value, 'time' => $time, 'expire' => $expire);
        $cacheData = serialize($cacheData);

        $putResult = file_put_contents($cacheFile, $cacheData);

        return $putResult !== false;
    }

    private function createCacheFile($key)
    {
        $cacheFile = $this->path($key);
        if (!file_exists($cacheFile)) {
            $directory = dirname($cacheFile);
            if (!is_dir($directory)) {
                $makeDirResult = mkdir($directory, 0755, true);
                if ($makeDirResult === false) {
                    return false;
                }
            }
            $createResult = touch($cacheFile);
            if ($createResult === false) {
                return false;
            }
        }

        return $cacheFile;
    }

    public function has($key): bool
    {
        $value = $this->get($key);

        return $value !== false;
    }

    public function increment($key, $value = 1)
    {
        $item = $this->getItem($key);
        if ($item === false) {
            $setResult = $this->set($key, $value);
            if ($setResult === false) {
                return false;
            }
            return $value;
        }

        $checkExpire = $this->checkExpire($item);
        if ($checkExpire === false) {
            return false;
        }

        $item['data'] += $value;

        $result = $this->setItem($key, $item['data'], $item['time'], $item['expire']);
        if ($result === false) {
            return false;
        }

        return $item['data'];
    }

    public function decrement($key, $value = 1)
    {
        $item = $this->getItem($key);
        if ($item === false) {
            $value = 0 - $value;
            $setResult = $this->set($key, $value);
            if ($setResult === false) {
                return false;
            }
            return $value;
        }

        $checkExpire = $this->checkExpire($item);
        if ($checkExpire === false) {
            return false;
        }

        $item['data'] -= $value;

        $result = $this->setItem($key, $item['data'], $item['time'], $item['expire']);
        if ($result === false) {
            return false;
        }

        return $item['data'];
    }

    public function delete($key): bool
    {
        $cacheFile = $this->path($key);
        if (file_exists($cacheFile)) {
            $unlinkResult = unlink($cacheFile);
            if ($unlinkResult === false) {
                return false;
            }
        }

        return true;
    }

    public function flush()
    {
        return $this->delTree($this->cachePath);
    }

    public function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    protected function path($key)
    {
        $parts = array_slice(str_split($hash = md5($key), 2), 0, 2);
        return $this->cachePath . '/' . implode('/', $parts) . '/' . $hash;
    }

    protected function getItem($key)
    {
        $cacheFile = $this->path($key);
        if (!file_exists($cacheFile) || !is_readable($cacheFile)) {
            return false;
        }

        $data = file_get_contents($cacheFile);
        if (empty($data)) {
            return false;
        }
        $cacheData = unserialize($data);

        if ($cacheData === false) {
            return false;
        }

        $checkExpire = $this->checkExpire($cacheData);
        if ($checkExpire === false) {
            $this->delete($key);
            return false;
        }

        return $cacheData;
    }

    protected function checkExpire($cacheData)
    {
        $time = time();
        $isExpire = (int) $cacheData['expire'] !== 0 && ((int) $cacheData['time'] + (int) $cacheData['expire'] < $time);
        if ($isExpire) {
            return false;
        }

        return true;
    }
}
