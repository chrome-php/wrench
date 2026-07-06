#!/usr/bin/env php
<?php

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Wrench\Application\DataHandlerInterface;
use Wrench\Application\StatusApplication;
use Wrench\Connection;
use Wrench\Server;

/**
 * Example server.
 *
 * This script will launch a websocket echo server at ws://localhost:8000/echo
 */

/* This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

\ini_set('display_errors', 1);
\error_reporting(\E_ALL);

require __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/StatusApplication.php';

/**
 * A simple PSR3 logger.
 */
$logger = new class extends AbstractLogger implements LoggerInterface {
    public function log($level, $message, array $context = []): void
    {
        echo \sprintf('[%s] %s - %s', $level, $message, \json_encode($context)).\PHP_EOL;
    }
};

/**
 * Our websocket server.
 */
$server = new Server('ws://localhost:8000/', [
    // 'logger' => $logger,
    'allowed_origins' => [
        'mysite.localhost',
    ],
]);

/**
 * Our example application, that just echoes the received data.
 */
$app = new class implements DataHandlerInterface {
    public function onData(string $data, Connection $connection): void
    {
        $connection->send($data);
    }
};

$server->setLogger($logger);
$server->registerApplication('echo', $app);
$server->registerApplication('status', new StatusApplication());
$server->run();
