<?php

namespace Tests\Feature\Throttles;

use App\Throttles\LoginPassable;
use Illuminate\Contracts\Pipeline\Hub;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Throwable;
class LoginPipelineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = Redis::connection('blocker');

        try {
            if ('PONG' !== (string)$this->redis->ping()) {
                $this->markTestSkipped('Redis is not ready');
            }
        } catch (Throwable $e) {
            $this->markTestSkipped('Redis is not ready');
        }
    }

    protected function tearDown(): void
    {
        $this->redis->flushdb();

        parent::tearDown();
    }

    /**
     * @test
     * @testdox 同 IP 同帳號連續存取超過限定次數，不會 alert
     */
    public function shouldReturnFalseWhenAccessOverMaxTimesWithSameIpAndSameAccount(): void
    {
        /** @var Hub $hub */
        $hub = $this->app->make(Hub::class);

        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever'), 'throttle.login');

        $actual = $hub->pipe(new LoginPassable('1.2.3.4', 'whatever'), 'throttle.login');
        $this->assertFalse($actual);
    }

    /**
     * @test
     * @testdox 同 IP 不同帳號連續存取，未超過限定次數，不會 alert
     */
    public function shouldReturnFalseWhenAccessUnderMaxTimesWithSameIpAndDiffAccount(): void
    {
        /** @var Hub $hub */
        $hub = $this->app->make(Hub::class);

        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever1'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever2'), 'throttle.login');

        $actual = $hub->pipe(new LoginPassable('1.2.3.4', 'whatever3'), 'throttle.login');
        $this->assertFalse($actual);
    }

    /**
     * @test
     * @testdox 同 IP 連續存取限定次數會 alert
     */
    public function shouldReturnTrueWhenAccessOverMaxTimesWithSameIpAndDiffAccount(): void
    {
        /** @var Hub $hub */
        $hub = $this->app->make(Hub::class);

        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever1'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever2'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever3'), 'throttle.login');

        $actual = $hub->pipe(new LoginPassable('1.2.3.4', 'whatever4'), 'throttle.login');
        $this->assertTrue($actual);

        $this->assertTrue($hub->pipe(new LoginPassable('1.2.3.4', 'whatever3'), 'throttle.login'));
    }

    /**
     * @test
     * @testdox 同 IP 連續存取限定次數後再清除次數，不會 alert
     */
    public function shouldReturnFalseWhenAccessOverMaxAndFlushWithSameIpAndDiffAccount(): void
    {
        /** @var Hub $hub */
        $hub = $this->app->make(Hub::class);

        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever1'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever2'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever3'), 'throttle.login');

        $this->redis->flushdb();

        $actual = $hub->pipe(new LoginPassable('1.2.3.4', 'whatever4'), 'throttle.login');
        $this->assertFalse($actual);
    }

    /**
     * @test
     * @testdox 不同 IP 分別各存一次，不會 alert
     */
    public function shouldDoNotAlertWhenAccessManyTimesWithDiffIp(): void
    {
        /** @var Hub $hub */
        $hub = $this->app->make(Hub::class);

        $hub->pipe(new LoginPassable('1.2.3.1', 'whatever1'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.2', 'whatever2'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.3', 'whatever3'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.4', 'whatever4'), 'throttle.login');
        $hub->pipe(new LoginPassable('1.2.3.5', 'whatever5'), 'throttle.login');

        $this->assertFalse($hub->pipe(new LoginPassable('1.2.3.1', 'whatever1'), 'throttle.login'));
        $this->assertFalse($hub->pipe(new LoginPassable('1.2.3.2', 'whatever2'), 'throttle.login'));
        $this->assertFalse($hub->pipe(new LoginPassable('1.2.3.3', 'whatever3'), 'throttle.login'));
        $this->assertFalse($hub->pipe(new LoginPassable('1.2.3.4', 'whatever4'), 'throttle.login'));
        $this->assertFalse($hub->pipe(new LoginPassable('1.2.3.5', 'whatever5'), 'throttle.login'));
    }

}
