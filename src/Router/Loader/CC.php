<?php

namespace Xel\Async\Router\Loader;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Swoole\Table;
use swoole\Coroutine as co;
use Swoole\Timer;

require_once __DIR__ . "/../../../vendor/autoload.php";

class CC
{
    private Table $classTable;
    private string $key = "class_data";
    private array $classes = [];
    private string $namespacePrefix = 'Xel\\Devise\\Service\\';

    private static bool $tableCreated = false;


    public function __construct()
    {
        if (!self::$tableCreated){
            $this->createTable();
            self::$tableCreated = true;
        }

        $cacheData = $this->classTable->get($this->key);

        // If the table doesn't exist, create it
        if ($cacheData === false && $this->classTable->exist($this->key) === false) {
            $this->createTable();
            $cacheData = $this->classTable->get($this->key);
        }

        $this->classes = $cacheData ? json_decode($cacheData['classes'], true) : [];
        $this->refreshCache();
    }


    public function getClasses(): array
    {
        return $this->classes;
    }

    private function createTable(): void
    {

        $this->classTable = new Table(1024);
        $this->classTable->column('classes', Table::TYPE_STRING,4096);
        $this->classTable->create();
    }

    public function refreshCache(): void
    {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../../devise/Service/');
        $iterator = new RecursiveIteratorIterator($directory);

        $startTime = microtime(true);

        $newClasses = [];
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
              $newClasses [] = $className;

            }
        }
        if($this->isDataChanged($newClasses)){
            $this->classes = $newClasses;
            // Save the new data to the Swoole table
            $this->classTable->set($this->key, ['classes' => json_encode($this->classes)]);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        echo "Execution time: $executionTime seconds\n";
    }

    private function isDataChanged(array $newData): bool
    {
        // Compare the old data with the new data structure
        return $this->classes !== $newData;
    }
}