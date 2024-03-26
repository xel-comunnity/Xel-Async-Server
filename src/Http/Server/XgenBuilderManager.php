<?php

namespace Xel\Async\Http\Server;
use Xel\DB\XgenConnector;

class XgenBuilderManager
{

    private XgenConnector $xgenConnector;
    public function __construct(array $config, bool $poolMode, int $pool)
    {
        $conn = new XgenConnector($config, $poolMode, $pool);
        $this->xgenConnector = $conn;
    }

    public function getConnection(): XgenConnector
    {
        return $this->xgenConnector;
    }
}