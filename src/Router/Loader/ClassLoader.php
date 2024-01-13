<?php

namespace Xel\Async\Router\Loader;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ClassLoader
{
    private string $namespacePrefix = 'Xel\\Devise\\';
    private array $classes = [];

    public function __construct()
    {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../../devise/Service/');
        $iterator = new RecursiveIteratorIterator($directory);

        $this->initialize($iterator);

    }

    public function initialize($iterator): void
    {
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {

                // ? Get the file path.
                $filePath = $fileInfo->getPathname();

                // ? Extract the relative class name.
                $relativeClassName = str_replace(
                    [DIRECTORY_SEPARATOR, '.php'],
                    ['\\', ''],
                    substr($filePath, strpos($filePath, 'Service'))
                );

                // ? Combine the namespace prefix and relative class name.
                $className = $this->namespacePrefix . $relativeClassName;

                // ? Store the class name in the array.
                $this->classes[] = $className;
            }
        }
    }

    public function getClasses(): array
    {
        return $this->classes;
    }
}

