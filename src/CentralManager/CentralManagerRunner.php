<?php

namespace Xel\Async\CentralManager;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response;
use Swoole\Server;
use Xel\Async\Contract\CentralManagerInterface;
use Xel\DB\QueryBuilder\QueryDML;

final class CentralManagerRunner implements CentralManagerInterface
{
    private array $jobBehaviour;

    private mixed $responses;

    private Server    $server;
    private Response  $response;
    private Container $container;

    public function __invoke
    (
        Server    $server,
        Response  $response,
        Container $container
    ): CentralManagerRunner
    {
      $this->server = $server;
      $this->response = $response;
      $this->container = $container;
      return $this;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function workSpace(callable $function):ResponseInterface
    {
        return $function($this->response, $this->queryDML());
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function doProcess(callable $function): CentralManagerRunner
    {

        $this->responses = $function($this->response, $this->queryDML());
        return $this;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function beforeExecute(string $job): CentralManagerRunner
    {
        $jobExec  = [
          'behaviour' => 'before',
          'instance' =>   $this->container->make($job)
        ];

        $this->jobBehaviour = $jobExec;
        return $this;
    }


    public function afterExecute(string $job): CentralManagerRunner
    {
        $jobExec  = [
            'behaviour' => 'after',
            'instance' =>  $job
        ];
        $this->jobBehaviour = $jobExec;
        return $this;
    }


    public function dispatch():void
    {
        if ($this->jobBehaviour['behaviour'] === 'before'){
            try {
                // ? run the process
                $response = $this->responses;

                // ? run the job after the process
                $this->server->task($this->jobBehaviour['instance']);
                // ? return result

                $this->response->setStatusCode(200);
                $this->response->end(json_encode($response));
            } catch (Exception $e) {
                $this->response->setStatusCode(422);
                $this->response->end(json_encode(["error" => $e->getMessage()]));
            }


        } else {
            // ? run the job before the process
            $this->server->task($this->jobBehaviour['instance']);
            try {
                $response = $this->responses;

                // ? return result
                $this->response->setStatusCode(200);
                $this->response->end(json_encode($response));
            } catch (Exception $e) {
                $this->response->setStatusCode(422);
                $this->response->end(json_encode(["error" => $e->getMessage()]));
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