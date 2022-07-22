<?php

namespace App\Throttles\Throughs;

use App\Events\Protection\Otp\RiskIp;
use App\Throttles\LoginPassable;
use App\Throttles\SetTypeRateLimiter;
use Closure;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 限制特定條件的 IP 觸發限制器
 */
class Limiter implements Throughable
{
    /**
     * @var int
     */
    private $decay;

    /**
     * @var int
     */
    private $max;

    /**
     * @var SetTypeRateLimiter
     */
    private $rateLimiter;

    public function __construct(SetTypeRateLimiter $rateLimiter, int $decay, int $max)
    {
        $this->rateLimiter = $rateLimiter;
        $this->decay = $decay;
        $this->max = $max;
    }

    public function handle(LoginPassable $passable, Closure $next): bool
    {
        try {
            if ($this->rateLimiter->tooManyAttempts($this->key($passable->ip), $this->max)) {
                Log::error('more_than_' . $this->max . '_in_' . $this->decay . 's', ['loginid' => $passable->loginId]);
                return true;
            }
        } catch (Throwable $e) {
            Log::error('Limiter: An error occurred during redis execution.', [
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $this->rateLimiter->hit($this->key($passable->ip), $passable->loginId, $this->decay);
        } catch (Throwable $e) {
            Log::error('Limiter: An error occurred during redis execution.', [
                'message' => $e->getMessage(),
            ]);
        }
        return $next($passable);
    }

    private function key(string $ip): string
    {
        return "{$this->decay}_{$this->max}:{$ip}";
    }
}
