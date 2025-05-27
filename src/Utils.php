<?php

namespace Kolmeya\Parallel;

class Utils
{
    public static function checkOverwriteRunMethod(string $childClass): void
    {
        $parentClass = Process::class;

        if ($childClass === $parentClass) {
            $message = "You should extend the `{$parentClass}`  and overwrite the run method";
            throw new \RuntimeException($message);
        }

        $child = new \ReflectionClass($childClass);

        if ($child->getParentClass() === false) {
            $message = "You should extend the `{$parentClass}`  and overwrite the run method";
            throw new \RuntimeException($message);
        }

        $parentMethods = $child->getParentClass()->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($parentMethods as $parentMethod) {
            if ($parentMethod->getName() !== 'run') {
                continue;
            }

            $declaringClass = $child->getMethod($parentMethod->getName())
                ->getDeclaringClass()
                ->getName();

            if ($declaringClass === $parentClass) {
                throw new \RuntimeException('You must overwrite the run method');
            }
        }
    }
}
