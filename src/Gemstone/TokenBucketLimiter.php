<?php

namespace Xel\Async\Gemstone;
class TokenBucketLimiter
{
    private int $lastRefill;
    private float $currentTokens;
    public function __construct
    (
        private readonly float $maxTokens,
        private readonly float $refillTokenRate,
        private readonly int   $interval,
    ){
        $this->lastRefill = time();
    }

    public function isPermitted(): bool
    {
        $this->refill();
        if ($this->currentTokens >= 1){
            $this->currentTokens--;
            return true;
        }
        return false;
    }

    public function refill(): void
    {
        if ($this->lastRefill() >= $this->lastRefill){
            $storeToken = floor($this->lastRefill() / $this->interval) * $this->refillTokenRate;
            $this->currentTokens = min($this->maxTokens , $this->currentTokens + $storeToken);
            $this->lastRefill = time();
        }
    }

    private function lastRefill(): int
    {
        return time() - $this->lastRefill;
    }
}