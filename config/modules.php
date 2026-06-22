<?php

return [
    'key' => env('MODULE_KEY', 'dms'),
    'remote_launch_secret' => env('MODULE_REMOTE_LAUNCH_SECRET', env('APP_KEY')),
    'remote_launch_ttl_seconds' => env('MODULE_REMOTE_LAUNCH_TTL', 120),
];
