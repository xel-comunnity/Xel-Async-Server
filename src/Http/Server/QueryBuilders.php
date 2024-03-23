<?php

namespace Xel\Async\Http\Server;

use PDO;
use Xel\DB\QueryBuilder\QueryBuilder;
use Xel\DB\XgenConnector;

class QueryBuilders
{
    private static ?QueryBuilder $queryBuilder = null;

    /**
     * @param XgenConnector|null $PDO
     * @param bool $mode
     * @return QueryBuilder|null
     */
    public static function getQueryBuilder(?XgenConnector $PDO, bool $mode): ?QueryBuilder
    {
        if (self::$queryBuilder == null){
            self::$queryBuilder = new QueryBuilder($PDO, $mode);
        }
        return self::$queryBuilder;
    }
}