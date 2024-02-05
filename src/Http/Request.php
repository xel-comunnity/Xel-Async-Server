<?php

namespace Xel\Async\Http;
use Xel\Async\Http\Utility\RequestDataHandler;

class Request
{
    use RequestDataHandler;

    public function __construct(
        private array $requestInterface,
        private ?array $uploadedFile = null
    ) {
    }

    /**
     * @param ...$select
     * @return array|string|null
     */
    public function getRequestInput(...$select):array|string|null
    {
        // Check if decoding was successful
        if ($this->requestInterface !== null) {
            // Flatten the array
            $flattenedArray = $this->populateArrayKey($this->requestInterface);
            $data =  $this->populateFlattenArray($flattenedArray, ...$select);
            if(count($select) === 1) {
              return $data[$select[0]];
            }
            return $data;
        }
        return null;
    }

    /**
     * @return array
     */
    public function getAllRequestData(): array
    {
        return $this->requestInterface;
    }

    public function getRequestFiles($name)
    {

    }

    public function getAllRequestFiles()
    {
        // Implement this method if needed
    }

    public function moveFile(string $path, $file)
    {
        // Implement this method if needed
    }
}