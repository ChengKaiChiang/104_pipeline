<?php

namespace App\Http\Controllers\Api;

use App\Throttles\LoginPassable;
use Illuminate\Contracts\Pipeline\Hub;
use Illuminate\Http\Request;

class DemoController
{
    public function __invoke(Request $request, Hub $hub)
    {
        $loginid = $request->input('loginid');
        $ips = $request->ips();
        $ip = end($ips);

        if ($hub->pipe(new LoginPassable($ip, $loginid), 'throttle.login') !== 'pass') {
            return response()->json(['status' => 'block']);
        }
        return response()->json(['status' => 'pass']);
    }
}
