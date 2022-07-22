<?php

namespace App\Throttles;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\InteractsWithTime;

/**
 * 模仿 Laravel 的 RateLimiter 寫一個 Set 的客製化版本
 *
 * @see \Illuminate\Cache\RateLimiter
 */
class SetTypeRateLimiter
{
    use InteractsWithTime;

    /**
     * @var Connection
     */
    protected $redis;

    /**
     * @param Connection $redis
     */
    public function __construct(Connection $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param string $key
     * @param int $maxAttempts
     * @return bool
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->attempts($key) >= $maxAttempts;
    }

    /**
     * 新增集合的元素
     *
     * @param string $key
     * @param string $element
     * @param int $decaySeconds
     * @return int
     */
    public function hit(string $key, string $element, int $decaySeconds): int
    {
        $isExist = $this->redis->exists($key);

        $this->redis->sadd($key, $element);
        if (!$isExist) {
            $this->redis->expire($key, $decaySeconds);
        }

        return $this->attempts($key);
    }

    /**
     * 取得集合的元素數量
     *
     * @param string $key
     * @return int
     */
    public function attempts(string $key): int
    {
        return $this->redis->scard($key);
    }

    /**
     * Reset the number of attempts for the given key.
     *
     * @param string $key
     * @return mixed
     */
    public function resetAttempts(string $key)
    {
        return $this->redis->del($key);
    }

    /**
     * Get the number of retries left for the given key.
     *
     * @param string $key
     * @param int $maxAttempts
     * @return int
     */
    public function retriesLeft(string $key, int $maxAttempts): int
    {
        $attempts = $this->attempts($key);

        return $maxAttempts - $attempts;
    }

    /**
     * Clear the hits and lockout timer for the given key.
     *
     * @param string $key
     * @return void
     */
    public function clear(string $key): void
    {
        $this->resetAttempts($key);
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     *
     * @param string $key
     * @return int
     */
    public function availableIn(string $key): int
    {
        return $this->redis->ttl($key);
    }
}
