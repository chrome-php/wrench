# Wrench

[![Latest Stable Version](https://poser.pugx.org/chrome-php/wrench/version)](https://packagist.org/packages/chrome-php/wrench)
[![License](https://poser.pugx.org/chrome-php/wrench/license)](https://packagist.org/packages/chrome-php/wrench)

A simple PHP WebSocket implementation.


## Installation

The library can be installed with Composer and is available on Packagist under
[chrome-php/chrome](https://packagist.org/packages/chrome-php/wrench):

```bash
$ composer require chrome-php/wrench
```

PHP 7.4-8.4 are currently supported, only.


## Usage

This creates a server on 127.0.0.1:8000 with one Application that listens for
WebSocket requests to `ws://localhost:8000/echo` and `ws://localhost:8000/chat`:

### Server

```php
// An example application, that just echoes the received
// data back to the connection that sent it
$app = new class implements \Wrench\Application\DataHandlerInterface
{
    public function onData(string $data, \Wrench\Connection $connection): void
    {
        $connection->send($data);
    }
};

// A websocket server, listening on port 8000
$server = new \Wrench\BasicServer('ws://localhost:8000', [
    'allowed_origins' => [
        'mysite.com',
        'mysite.dev.localdomain'
    ],
]);

$server->registerApplication('echo', $app);
$server->registerApplication('chat', new \My\ChatApplication());
$server->setLogger($monolog); // PSR3
$server->run();
```

### Client

```php
// A client side example, that sends a string and will receive
// the data back to the connection that sent it
$client = new Client('ws://localhost:8000', 'http://localhost:8000');
$client->connect();
$client->sendData('hello');
$response = $client->receive()[0]->getPayload();
$client->disconnect();
```


## Contributing

See [CONTRIBUTING.md](.github/CONTRIBUTING.md) for contribution details.


## License

This project is licensed under the [The MIT License (MIT)](LICENSE).
