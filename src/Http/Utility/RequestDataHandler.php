<?php

namespace Xel\Async\Http\Utility;

trait RequestDataHandler
{
    private function populateArrayKey($array, $parentKey = null): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $currentKey = ($parentKey !== null) ? $parentKey."_".$key : $key;
            if (is_array($value)) {
                // Recursively flatten nested arrays
                $result = array_merge($result, $this->populateArrayKey($value, $currentKey));
            } else {
                // Store the value in the result array using the extracted key
                $result[$currentKey] = $value;
            }
        }

        return $result;
    }

    private function populateFlattenArray($flattenedArray, ...$keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $flattenedArray)) {
                $result[$key] = $flattenedArray[$key];
            } else {
                // Handle cases where the key is not found
                $result[$key] = null; // You can set a default value or handle it as needed
            }
        }
        return $result;
    }

}