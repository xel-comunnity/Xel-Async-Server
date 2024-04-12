<?php

namespace Xel\Async\Contract;
use Psr\Http\Message\ResponseInterface;

interface CentralManagerInterface
{
    /*******************************************************************************************************************
     * Normal case Service Process
     *******************************************************************************************************************/
    public function workSpace(callable $function):ResponseInterface;


    /*******************************************************************************************************************
     * Dispatch Async Task with process
     *******************************************************************************************************************/
    public function doProcess(callable $function);
    public function beforeExecute(string $job);
    public function afterExecute(string $job);

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