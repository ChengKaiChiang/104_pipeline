<?php

namespace App\Throttles\Throughs;

use App\Throttles\LoginPassable;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;

/**
 * 白名單帳號
 *
 * 名單內的帳號，可以通過限制
 *
 * 設計上是把 RateLimiter 反過來用，當需要加白名單，會使用 hit 先記一次，而要確認是否在白名單時，只要確定超過一次就算成功。
 */
class AllowPid implements Throughable
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
        if (null !== $passable->pid && $this->has($passable->pid)) {
            Log::info('Login account in allow list', [
                'account' => $passable->loginId,
            ]);

            return 'pass';
        }

        return $next($passable);
    }

    /**
     * 帳號加入白名單
     */
    public function add(string $pid, int $decay): void
    {
        $this->rateLimiter->hit($this->key($pid), $decay);
    }

    /**
     * 確認帳號是否在白名單裡
     */
    public function has(string $pid): bool
    {
        return $this->rateLimiter->tooManyAttempts($this->key($pid), 1);
    }

    /**
     * 清除帳號白名單
     */
    public function forget(string $pid): bool
    {
        return $this->rateLimiter->resetAttempts($this->key($pid));
    }

    private function key(string $pid): string
    {
        return "allow_pid:{$pid}";
    }
}
