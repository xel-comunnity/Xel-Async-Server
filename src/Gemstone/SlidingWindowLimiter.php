<?php

namespace Xel\Async\Gemstone;
use Exception;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Table;

class SlidingWindowLimiter
{
    private Table $table;
    private int $windowSize;
    private int $maxRequests;
    private ?int $maxRequestsPerMinute = null;
    private ?string $blacklistedIpsFile = null;

    private array $black_list;
    private array $blacklistedIps;

    public function __construct(int $maxRequests, int $windowSize, array $blacklist)
    {
        $this->table = new Table(1024);
        $this->table->column('requests', Table::TYPE_INT, 8);
        $this->table->column('lastResetTime', Table::TYPE_INT, 8);
        $this->table->column('blocked', Table::TYPE_INT, 1);
        $this->table->create();

        $this->windowSize = $windowSize;
        $this->maxRequests = $maxRequests;

        $this->black_list = $blacklist;
        $this->maxRequestsPerMinute = $blacklist[0] ?? null;
        $this->blacklistedIpsFile = $blacklist[1] ?? null;
        $this->blacklistedIps = $this->loadBlacklistedIps();
    }

    /**
     * @throws Exception
     */
    public function isAllowed(Request $request, Response $response): bool
    {
        $key = $request->header['x-forwarded-for'] ?? $request->server['remote_addr'];

        if (in_array($key,$this->blacklistedIps,true)){
            throw new Exception("IP address $key is blocked.", 403);

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
            throw new Exception("IP address $key is blocked.", 403);
        }

        if ($currentTime - $lastResetTime > $this->windowSize) {
            $this->table->set($key, 
            ['requests' => 1, 'lastResetTime' => $currentTime, 'blocked' => 0]
        );
            return true;
        }
        // a sample test v10
        if (count($this->black_list) > 0){
            $this->blockIp($requests, $key);
        }else{
            $this->passIp($requests, $key);
        }

        $this->table->incr($key, 'requests', 1);
        return true;
    }

    /**
     * @throws Exception
     */
    private function blockIp($requests, $key): void
    {
        if ($requests >= $this->maxRequestsPerMinute) {
            $this->blacklistIp($key);
            throw new Exception("Too many requests per minute. IP address $key has been blocked.", 403);
        }
    }

    /**
     * @throws Exception
     */
    private function passIp($requests): void
    {
        if ($requests >= $this->maxRequests) {
            throw new Exception("Too many requests per minute.", 429);
        }
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

