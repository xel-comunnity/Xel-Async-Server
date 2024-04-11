<?php

namespace Xel\Async\Contract;
interface JobDispatcherInterface
{
    public function doProcess(callable $function);
    public function beforeExecute(string $job);
    public function afterExecute(string $job);
}