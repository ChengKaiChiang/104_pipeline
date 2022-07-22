<?php

namespace App\Throttles;

class LoginPassable
{
    /**
     * @var string
     */
    public $ip;

    /**
     * @var string
     */
    public $loginId;

    /**
     * @var string|null
     */
    public $pid;

    public function __construct(string $ip, string $loginId, ?string $pid = null)
    {
        $this->ip = $ip;
        $this->loginId = $loginId;
        $this->pid = $pid;
    }
}
