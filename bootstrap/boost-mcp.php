#!/usr/bin/env php
<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Laravel\Mcp\Server\Registrar;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require __DIR__.'/app.php';

ob_start();

try {
    $app->make(Kernel::class)->bootstrap();
} catch (Throwable $throwable) {
    ob_end_flush();

    throw $throwable;
}

ob_end_clean();

$server = $app->make(Registrar::class)->getLocalServer('laravel-boost');

if ($server === null) {
    fwrite(STDERR, 'MCP Server with name [laravel-boost] not found. Did you register it using [Mcp::local()]?'.PHP_EOL);

    exit(1);
}

$server();
