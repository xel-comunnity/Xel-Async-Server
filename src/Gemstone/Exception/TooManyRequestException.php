<?php

namespace Xel\Async\Gemstone\Exception;

use Exception;
use Throwable;

class TooManyRequestException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}