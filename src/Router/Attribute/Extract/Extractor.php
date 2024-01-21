<?php

namespace Xel\Async\Router\Attribute\Extract;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use Xel\Async\Router\Attribute\Router;

class Extractor
{
    /**
     * @var array<int|string, mixed>
     */
    private array $param = [];

    /**
     * @param array<string, mixed> $loader
     * @return array<int|string, mixed>
     * @throws ReflectionException
     */
    public function setLoader(array $loader): array
    {
       return $this->Extract($loader);
    }

    /**
     * @param array<string, mixed> $loader
     * @return array<int|string, mixed>
     * @throws ReflectionException
     */
    private function Extract(array $loader): array
    {
        foreach ($loader as $value){
            $reflection = new ReflectionClass($value);
            $methods = $reflection->getMethods();
            foreach ($methods as $method) {
                $attr = $method->getAttributes(Router::class, ReflectionAttribute::IS_INSTANCEOF);
                foreach ($attr as $attribute) {
                    $getAttrInstance = $attribute->newInstance();
                    $getMethod = $method->getName();
                    $tmp = [
                        "Uri" => $getAttrInstance->getPath(),
                        "RequestMethod" => $getAttrInstance->getMethod(),
                        "Class" => $value,
                        "Method" => $getMethod,
                    ];
                    $this->param[] = $tmp;
                }
            }
        }

        return $this->param;
    }

}