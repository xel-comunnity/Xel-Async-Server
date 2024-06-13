<?php

namespace Xel\Async\Gemstone;
namespace Xel\Async\Gemstone;
use Exception;
use Swoole\Server;
use Swoole\Table;

class SlidingWindowsLimiter_V2
{
    private Table $table;
    private int $windowSize;
    private int $maxConnections;
    private bool $enableBlacklisting;
    private ?string $blacklistedIpsFile = null;

    private array $blacklistedIps;

    public function __construct(int $maxConnections, int $windowSize, bool $enableBlacklisting = false, ?string $blacklistedIpsFile = null)
    {
        $this->table = new Table(1024);
        $this->table->column('connections', Table::TYPE_INT, 8);
        $this->table->column('lastResetTime', Table::TYPE_INT, 8);
        $this->table->create();

        $this->windowSize = $windowSize;
        $this->maxConnections = $maxConnections;
        $this->enableBlacklisting = $enableBlacklisting;
        $this->blacklistedIpsFile = $blacklistedIpsFile;
        $this->blacklistedIps = $this->loadBlacklistedIps();
    }

    /**
     * @throws Exception
     */
    public function isAllowed(Server $server, int $fd): bool
    {
        $clientInfo = $server->getClientInfo($fd);
        $key = $clientInfo['remote_ip'];

        if ($this->enableBlacklisting && in_array($key, $this->blacklistedIps, true)) {
            throw new Exception("IP address $key is blocked.", 403);
        }

        $currentTime = time();
        $connections = 0;
        $lastResetTime = 0;
        if ($this->table->exist($key)) {
            $connections = $this->table->get($key, 'connections');
            $lastResetTime = $this->table->get($key, 'lastResetTime');
        }

        if ($currentTime - $lastResetTime > $this->windowSize) {
            $this->table->set($key, ['connections' => 1, 'lastResetTime' => $currentTime]);
            return true;
        }

        if ($connections >= $this->maxConnections) {
            throw new Exception("Too many connections per minute.", 429);
        }

        $this->table->incr($key, 'connections', 1);
        return true;
    }

    public function enableBlacklisting(bool $enable): void
    {
        $this->enableBlacklisting = $enable;
    }

    public function addToBlacklist(string $ip): void
    {
        if (!in_array($ip, $this->blacklistedIps, true)) {
            $this->blacklistedIps[] = $ip;
            $this->saveBlacklistedIps();
        }
    }

    public function removeFromBlacklist(string $ip): void
    {
        $key = array_search($ip, $this->blacklistedIps, true);
        if ($key !== false) {
            unset($this->blacklistedIps[$key]);
            $this->saveBlacklistedIps();
        }
    }

    private function loadBlacklistedIps(): array
    {
        if ($this->blacklistedIpsFile && file_exists($this->blacklistedIpsFile)) {
            $blacklistedIps = include $this->blacklistedIpsFile;
            return is_array($blacklistedIps) ? $blacklistedIps : [];
        }
        return [];
    }

    private function saveBlacklistedIps(): void
    {
        if ($this->blacklistedIpsFile) {
            file_put_contents($this->blacklistedIpsFile, '<?php return ' . var_export($this->blacklistedIps, true) . ';');
        }
    }
}