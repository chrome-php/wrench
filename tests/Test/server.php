<?php

$loader = require __DIR__.'/../../vendor/autoload.php';

if (2 != $argc || !$argv[1] || !\is_numeric($argv[1]) || (int) $argv[1] <= 1024) {
    throw new InvalidArgumentException('Invalid port number: supply as first argument');
}

$port = (int) $argv[1];

$server = new Wrench\Server('ws://localhost:'.$port);

$app = new class() implements \Wrench\Application\DataHandlerInterface {
    public function onData(string $data, Wrench\Connection $connection): void
    {
        $connection->send($data);
    }
};
$server->registerApplication('echo', $app);

$server->run();
