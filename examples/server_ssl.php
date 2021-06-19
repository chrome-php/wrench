#!/usr/bin/env php
<?php

/**
 * Example server.
 *
 * This script will launch a websocket echo server at wss://localhost:8000/echo
 *
 * This SSL server uses a self-signed certificate. This means your browser will probably
 * refuse to make Websocket connections to it. Chrome does *not* show this in the inspector.
 *
 * To add an exception, visit https://127.0.0.1:8000/ after starting this server. Then you'll
 * get the normal security prompt.
 */
\ini_set('display_errors', 1);
\error_reporting(\E_ALL);

require __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/StatusApplication.php';

// Generate PEM file
$pemFile = __DIR__.'/generated.pem';
$pemPassphrase = null;
$countryName = 'DE';
$stateOrProvinceName = 'none';
$localityName = 'none';
$organizationName = 'none';
$organizationalUnitName = 'none';
$commonName = '127.0.0.1';
$emailAddress = 'someone@example.com';

Wrench\Util\Ssl::generatePEMFile(
    $pemFile,
    $pemPassphrase,
    $countryName,
    $stateOrProvinceName,
    $localityName,
    $organizationName,
    $organizationalUnitName,
    $commonName,
    $emailAddress
);

// User can use tls in place of ssl
$server = new \Wrench\Server('wss://127.0.0.1:8000/', [
    'connection_manager_options' => [
        'socket_master_options' => [
            'server_ssl_local_cert' => $pemFile,
            'server_ssl_passphrase' => $pemPassphrase,
            'server_ssl_allow_self_signed' => true,
            'server_ssl_verify_peer' => false,
        ],
    ],
]);

/**
 * Our example application, that just echoes the received data.
 */
$app = new class() implements \Wrench\Application\DataHandlerInterface {
    public function onData(string $data, Wrench\Connection $connection): void
    {
        $connection->send($data);
    }
};

$server->registerApplication('echo', $app);
$server->registerApplication('status', new \Wrench\Application\StatusApplication());

$server->run();
