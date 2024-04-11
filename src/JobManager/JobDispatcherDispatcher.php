<?php

namespace Xel\Async\JobManager;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use phpDocumentor\Reflection\Types\Callable_;
use Psr\Http\Message\ResponseInterface;
use Swoole\Server;
use Xel\Async\Contract\JobDispatcherInterface;
use Xel\Async\Http\Response;
use Xel\DB\QueryBuilder\QueryDML;

final class JobDispatcherDispatcher implements JobDispatcherInterface
{
    private array $jobBehaviour;
    private ResponseInterface $responseInterface;

    public function __construct
    (
        private readonly Server    $server,
        private Response           $response,
        private readonly Container $container
    ){}

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function doProcess(callable $function):void
    {
        $response = $function($this->response, $this->queryDML());
        $this->responseInterface = $response;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function beforeExecute(string $job): JobDispatcherDispatcher
    {
        $jobExec  = [
          'behaviour' => 'before',
          'instance' =>   $this->container->make($job)
        ];
        $this->jobBehaviour = $jobExec;
        return $this;
    }


    public function afterExecute(string $job): JobDispatcherDispatcher
    {
        $jobExec  = [
            'behaviour' => 'after',
            'instance' =>  $job
        ];
        $this->jobBehaviour = $jobExec;
        return $this;
    }

    public function onTask()
    {}
    public function onFinish()
    {}

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function dispatch(): ResponseInterface
    {
        if ($this->jobBehaviour['behaviour'] === 'before'){
            try {
                // ? run the process
                $response = $this->responseInterface;
                // ? run the job after the process
               $this->server->task($this->jobBehaviour['instance']);
                // ? return result
                return $response;
            } catch (Exception $e) {
                return $this->response->json(["error" => $e->getMessage()], 422);
            }


        } else {
            // ? run the job before the process
            $this->server->task($this->jobBehaviour['instance']);
            try {
                // ? run the process
                return $this->responseInterface;
            } catch (Exception $e) {
                return $this->response->json(["error" => $e->getMessage()], 422);
            }
        }
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function queryDML(): QueryDML
    {
        /***
         * @var QueryDML $instance
         */
        $instance =$this->container->get('xgen');
        return $instance;
    }


}