<?php

namespace App\Throttles\Throughs;

use App\Throttles\LoginPassable;
use Closure;

interface Throughable
{
    public function handle(LoginPassable $passable, Closure $next): bool;
}
