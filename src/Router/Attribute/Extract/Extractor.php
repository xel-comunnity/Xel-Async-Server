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
        $cacheFile = __DIR__ . '/../../../test/cache/class_cache';
        if (file_exists($cacheFile)) {
            // Use cached data if available
            $this->param = include $cacheFile;
        } else {
            // Generate and cache data if not available
            $this->param = $this->extract($loader);
            file_put_contents($cacheFile, '<?php return ' . var_export($this->param, true) . ';');
        }

        return $this->param;
    }

    /**
     * @param array<string, mixed> $loader
     * @return array<int|string, mixed>
     * @throws ReflectionException
     */
    private function extract(array $loader): array
    {
        foreach ($loader as $value) {
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
























//namespace Xel\Async\Router\Attribute\Extract;
//
//use ReflectionAttribute;
//use ReflectionClass;
//use ReflectionException;
//use Xel\Async\Router\Attribute\Router;
//
//class Extractor
//{
//    /**
//     * @var array<int|string, mixed>
//     */
//    private array $param = [];
//
//    /**
//     * @param array<string, mixed> $loader
//     * @return array<int|string, mixed>
//     * @throws ReflectionException
//     */
//    public function setLoader(array $loader, string $path = ""): array
//    {
//       return $this->Extract($loader);
//    }
//
//    /**
//     * @param array<string, mixed> $loader
//     * @return array<int|string, mixed>
//     * @throws ReflectionException
//     */
//    private function Extract(array $loader): array
//    {
//        foreach ($loader as $value){
//            $reflection = new ReflectionClass($value);
//            $methods = $reflection->getMethods();
//            foreach ($methods as $method) {
//                $attr = $method->getAttributes(Router::class, ReflectionAttribute::IS_INSTANCEOF);
//                foreach ($attr as $attribute) {
//                    $getAttrInstance = $attribute->newInstance();
//                    $getMethod = $method->getName();
//                    $tmp = [
//                        "Uri" => $getAttrInstance->getPath(),
//                        "RequestMethod" => $getAttrInstance->getMethod(),
//                        "Class" => $value,
//                        "Method" => $getMethod,
//                    ];
//                    $this->param[] = $tmp;
//                }
//            }
//        }
//
//        return $this->param;
//    }
//
//}