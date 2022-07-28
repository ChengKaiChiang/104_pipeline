<?php

namespace App\Throttles\Throughs;

use App\Throttles\LoginPassable;
use Closure;
use Illuminate\Cache\RateLimiter;

/**
 * 黑名單 IP 限制器
 *
 * 在名單內的話，必定觸發阻擋
 */
class BlockIp implements Throughable
{
    /**
     * @var RateLimiter
     */
    private $rateLimiter;

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    public function handle(LoginPassable $passable, Closure $next)
    {
        if ($this->isLock($passable->ip)) {
            return true;
        }

        return $next($passable);
    }

    /**
     * 針對 IP 阻擋
     */
    public function block(string $ip, int $decay): void
    {
        $this->rateLimiter->hit($this->key($ip), $decay);
    }

    /**
     * 確認 IP 鎖定狀態
     */
    public function isLock(string $ip): bool
    {
        return $this->rateLimiter->tooManyAttempts($this->key($ip), 1);
    }

    /**
     * 解鎖 IP
     */
    public function unlock(string $ip): void
    {
        $this->rateLimiter->resetAttempts($this->key($ip));
    }

    private function key(string $ip): string
    {
        return "block_ip:{$ip}";
    }
}
