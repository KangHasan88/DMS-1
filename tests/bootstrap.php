<?php

$configCache = dirname(__DIR__).'/bootstrap/cache/config.php';

if (is_file($configCache)) {
    fwrite(
        STDERR,
        "Refusing to run tests with cached production config. Run `composer test` or `php8.3 artisan config:clear` first.\n"
    );

    exit(1);
}

require dirname(__DIR__).'/vendor/autoload.php';
