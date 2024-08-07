<?php

namespace Xel\Async\Contract;
use Xel\Async\CentralManager\CentralManagerRunner;

interface CentralManagerInterface
{
    /*******************************************************************************************************************
     * Normal case Service Process
     *******************************************************************************************************************/
    public function workSpace(callable $function):void;

    /*******************************************************************************************************************
     * Dispatch Async Task with process
     *******************************************************************************************************************/
    public function doProcess(callable $function);
    public function beforeExecute(string $job);
    public function afterExecute(string $job);
    public function loadBaseData(...$BaseData): CentralManagerRunner;

    /*******************************************************************************************************************
     * Optimization Process
     *******************************************************************************************************************/
    // ? Simple cache system
    // ? Batch Process

    /*******************************************************************************************************************
     * Gemstone
     *******************************************************************************************************************/
    // ? file compressor (based on mime type)
    // ? store file handler
    // ? Secure post request Handler
}