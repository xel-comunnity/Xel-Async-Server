<?php

namespace Xel\Async\Http\Server;
use Xel\DB\QueryBuilder\QueryBuilder;
use Xel\DB\XgenConnector;

class QueryBuildersManager
{
    private QueryBuilder $queryBuilder;
     public function __construct(XgenConnector $xgenConnector, bool $mode)
     {
         $conn = new QueryBuilder($xgenConnector,$mode);
         $this->queryBuilder = $conn;
     }

     public function getQueryBuilder(): QueryBuilder
     {
         return $this->queryBuilder;
     }
}