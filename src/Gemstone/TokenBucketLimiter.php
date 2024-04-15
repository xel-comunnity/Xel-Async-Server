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
        $this->currentTokens = $this->maxTokens;
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
        $timeSinceLastRefill = time() - $this->lastRefill;
        if ($timeSinceLastRefill >= $this->interval){
            $storeToken = floor($timeSinceLastRefill / $this->interval) * $this->refillTokenRate;
            $this->currentTokens = min($this->maxTokens , $this->currentTokens + $storeToken);
            $this->lastRefill = time();
        }
    }
}