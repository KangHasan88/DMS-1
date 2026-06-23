<?php

return [
    'key' => env('MODULE_KEY', 'dms'),
    'remote_launch_secret' => env('MODULE_REMOTE_LAUNCH_SECRET'),
    'remote_launch_ttl_seconds' => env('MODULE_REMOTE_LAUNCH_TTL', 120),
    'remote_provision_secret' => env('MODULE_REMOTE_PROVISION_SECRET'),
    'remote_provision_ttl_seconds' => env('MODULE_REMOTE_PROVISION_TTL', 300),
    'central_provisioning_callback_url' => env('MODULE_CENTRAL_PROVISIONING_CALLBACK_URL'),
    'central_health_callback_url' => env('MODULE_CENTRAL_HEALTH_CALLBACK_URL'),
];
