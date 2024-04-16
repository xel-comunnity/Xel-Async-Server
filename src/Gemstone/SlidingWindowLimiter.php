<?php

namespace Xel\Async\Gemstone;
use Exception;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Table;
use Xel\Async\Gemstone\Exception\BlackListException;

class SlidingWindowLimiter
{
    private Table $table;
    private int $windowSize;
    private int $maxRequests;
    private int $maxRequestsPerMinute;
    private string $blacklistedIpsFile;
    private array $blacklistedIps;

    public function __construct(int $maxRequests, int $windowSize, int $maxRequestsPerMinute, string $blacklistedIpsFile)
    {
        $this->table = new Table(1024);
        $this->table->column('requests', Table::TYPE_INT, 8);
        $this->table->column('lastResetTime', Table::TYPE_INT, 8);
        $this->table->column('blocked', Table::TYPE_INT, 1);
        $this->table->create();

        $this->windowSize = $windowSize;
        $this->maxRequests = $maxRequests;
        $this->maxRequestsPerMinute = $maxRequestsPerMinute;
        $this->blacklistedIpsFile = $blacklistedIpsFile;
        $this->blacklistedIps = $this->loadBlacklistedIps();
    }


    /**
     * @throws Exception
     */
    public function isAllowed(Request $request, Response $response): bool
    {
        $key = $request->header['x-forwarded-for'] ?? $request->server['remote_addr'];

        if (in_array($key, $this->blacklistedIps, true)) {
            $response->setStatusCode(403, 'Forbidden Access');
            throw new BlackListException("IP address $key is blocked.");
        }

        $currentTime = time();
        $requests = 0;
        $lastResetTime = 0;
        $blocked = 0;

        if ($this->table->exist($key)) {
            $requests = $this->table->get($key, 'requests');
            $lastResetTime = $this->table->get($key, 'lastResetTime');
            $blocked = $this->table->get($key, 'blocked');
        }

        if ($blocked) {
            throw new BlackListException("IP address $key is blocked.");
        }

        if ($currentTime - $lastResetTime > $this->windowSize) {
            $this->table->set($key, ['requests' => 1, 'lastResetTime' => $currentTime, 'blocked' => 0]);
            return true;
        }

        if ($requests >= $this->maxRequests) {
            throw new Exception("Too many requests. Please try again in a few minutes.");
        }

        if ($requests >= $this->maxRequestsPerMinute) {
            $this->blacklistIp($key);
            throw new Exception("Too many requests per minute. IP address $key has been blocked.");
        }

        $this->table->incr($key, 'requests', 1);
        return true;
    }


    private function blacklistIp(string $ip): void
    {
        $this->blacklistedIps[] = $ip;
        $this->table->set($ip, ['requests' => 0, 'lastResetTime' => 0, 'blocked' => 1]);
        $this->saveBlacklistedIps();
    }

    private function loadBlacklistedIps(): array
    {
        if (file_exists($this->blacklistedIpsFile)) {
            $blacklistedIps = include $this->blacklistedIpsFile;
            return is_array($blacklistedIps) ? $blacklistedIps : [];
        }
        return [];
    }

    private function saveBlacklistedIps(): void
    {
        file_put_contents($this->blacklistedIpsFile, '<?php return ' . var_export($this->blacklistedIps, true) . ';');
    }
}

