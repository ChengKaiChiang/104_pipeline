<?php

$staticBasePath = env('STATIC_BASE_PATH');

return [
    'throttles' => [
        'login' => [
            // e.g. decay,max >> 60,3|3600,5|86400,8
            'rule' => env('CORP104_THROTTLES_LOGIN_RULE'),
        ]
    ],
];
