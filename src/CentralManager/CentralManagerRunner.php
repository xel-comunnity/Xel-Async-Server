<?php

namespace Xel\Async\CentralManager;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Swoole\Server;
use Xel\Async\Contract\CentralManagerInterface;
use Xel\Async\Http\Responses;
use Xel\DB\QueryBuilder\QueryDML;

final class CentralManagerRunner implements CentralManagerInterface
{
    private array $jobBehaviour;
    private array $currentModel;
    private mixed $responses;
    private bool $useQueryBuilder = true;

    private Server    $server;
    private Responses  $response;
    private Container $container;

    public function __invoke
    (
        Server    $server,
        Responses  $response,
        Container $container
    ): CentralManagerRunner
    {
      $this->server = $server;
      $this->response = $response;
      $this->container = $container;
      return $this;
    }
    /*******************************************************************************************************************
     * Regular Operation
     ******************************************************************************************************************/

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function workSpace(callable $function):void
    {
        if($this->useQueryBuilder === false){
            $function($this->response, ...$this->currentModel);
        }

        $function($this->response, $this->queryDML());
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function loadBaseData(...$BaseData): CentralManagerRunner
    {
        $list = $this->container->get('basedata');
        $param = [];

        foreach ($BaseData as $value){
            $param[$value] = new $list[$value]($this->queryDML());
        }

        $this->currentModel = $param;
        $this->useQueryBuilder = false;
        return $this;
    }


    /*******************************************************************************************************************
     *
     ******************************************************************************************************************/

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
                $this->response->json($response, false, 200);
            } catch (Exception $e) {
                $this->response->json(["error" => $e->getMessage()], false, 200);
            }


        } else {
            // ? run the job before the process
            $this->server->task($this->jobBehaviour['instance']);
            try {
                $response = $this->responses;

                // ? return result
                $this->response->json($response, false, 200);
            } catch (Exception $e) {
                $this->response->json(["error" => $e->getMessage()], false, 200);

            }
        }
    }
    /*******************************************************************************************************************
     *
     ******************************************************************************************************************/

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