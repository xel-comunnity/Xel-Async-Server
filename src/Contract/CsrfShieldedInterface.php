<?php

namespace Xel\Async\Contract;

interface CsrfShieldedInterface
{
    public function generateCSRFToken($key): string;

    public function validateToken($csrfToken, $key): bool;
}