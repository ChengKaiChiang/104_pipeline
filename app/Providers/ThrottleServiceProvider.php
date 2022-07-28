<?php

namespace App\Providers;

use App\Throttles\LoginPassable;
use App\Throttles\SetTypeRateLimiter;
use App\Throttles\Throughs\AllowPid;
use App\Throttles\Throughs\BlockIp;
use App\Throttles\Throughs\Limiter;
use Illuminate\Contracts\Pipeline\Hub as HubContract;
use Illuminate\Pipeline\Hub;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

use function explode;

class ThrottleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        /** @var Hub $hub */
        $hub = $this->app->make(HubContract::class);

        $limiters = $this->createLimiters();
        array_unshift(
            $limiters,
            $this->app->make(AllowPid::class),
            $this->app->make(BlockIp::class)
        );

        $hub->pipeline('throttle.login', function (Pipeline $pipeline, LoginPassable $passable) use ($limiters) {
            return $pipeline->send($passable)
                ->through($limiters)
                ->then(function () {
                    return 'pass';
                });
        });
    }

    public function register()
    {
        $this->app->singleton(SetTypeRateLimiter::class, function () {
            return new SetTypeRateLimiter(Redis::connection('blocker'));
        });
    }

    private function createLimiters(): array
    {
        $limiters = [];

        $rules = config('corp104.throttles.login.rule');

        // 如果沒設定規則的話，等於功能關閉
        if (empty($rules)) {
            return $limiters;
        }

        /** @var SetTypeRateLimiter $rateLimiter */
        $rateLimiter = $this->app->make(SetTypeRateLimiter::class);

        foreach (explode('|', $rules) as $rule) {
            $limiters[] = $this->createLimiter($rateLimiter, $rule);
        }


        return $limiters;
    }

    private function createLimiter(SetTypeRateLimiter $rateLimiter, string $rule): Limiter
    {
        $params = explode(',', $rule);

        if (count($params) !== 2) {
            throw new InvalidArgumentException("Login throttle's env is invalid.");
        }

        return new Limiter($rateLimiter, (int)$params[0], (int)$params[1]);
    }
}
