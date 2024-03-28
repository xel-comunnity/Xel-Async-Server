<?php

namespace Xel\Async\Http\Server;
use Xel\DB\Contract\QueryDMLInterface;
use Xel\DB\QueryBuilder\QueryDML;
use Xel\DB\XgenConnector;

class QueryBuildersManager
{
    private QueryDMLInterface $queryBuilder;
     public function __construct(XgenConnector $xgenConnector, bool $mode)
     {
         $conn = new QueryDML($xgenConnector,$mode);
         $this->queryBuilder = $conn;
     }

     public function getQueryBuilder(): QueryDML
     {
         return $this->queryBuilder;
     }
}