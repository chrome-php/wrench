<?php

namespace Wrench;

use InvalidArgumentException;
use TypeError;
use Wrench\Payload\Payload;
use Wrench\Protocol\Protocol;
use Wrench\Protocol\Rfc6455Protocol;
use Wrench\Socket\ClientSocket;
use Wrench\Test\BaseTest;
use Wrench\Test\ServerTestHelper;

class ClientTest extends BaseTest
{
    public function testConstructor(): void
    {
        $this->assertInstanceOfClass(
            new Client(
                'ws://localhost/test',
                'http://example.org/'
            ),
            'ws:// scheme, default socket'
        );
    }

    public function testConstructorWithSocket(): void
    {
        $this->assertInstanceOfClass(
            new Client(
                'ws://localhost/test',
                'http://example.org/',
                ['socket' => $this->getMockSocket()]
            ),
            'ws:// scheme, socket specified'
        );
    }

    private function getMockSocket(): ClientSocket
    {
        return $this->getMockBuilder(ClientSocket::class)
            ->onlyMethods([])
            ->setConstructorArgs(['wss://localhost:8000'])
            ->getMock();
    }

    /**
     * Gets a client whose socket answers the handshake with the chunks the
     * callback makes out of the response.
     */
    private function getInstanceWithResponse(callable $chunk): Client
    {
        $socket = $this->getMockBuilder(ClientSocket::class)
            ->onlyMethods(['connect', 'isConnected', 'send', 'receive'])
            ->setConstructorArgs(['ws://localhost:8000'])
            ->getMock();

        $chunks = [];

        $socket->method('connect')->willReturn(true);
        $socket->method('isConnected')->willReturn(true);
        $socket->method('send')->willReturnCallback(static function (string $data) use (&$chunks, $chunk): int {
            if (\preg_match('/Sec-WebSocket-Key: (.*)\r\n/i', $data, $matches)) {
                $chunks = $chunk(\sprintf(
                    "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: %s\r\n\r\n",
                    (new Rfc6455Protocol())->getEncodedHash($matches[1])
                ));
            }

            return \strlen($data);
        });
        $socket->method('receive')->willReturnCallback(static function () use (&$chunks): string {
            return (string) \array_shift($chunks);
        });

        $instance = self::getInstance('ws://localhost/test', 'http://example.org/');

        (function () use ($socket): void {
            $this->socket = $socket;
        })->call($instance);

        return $instance;
    }

    public function testConstructorUriInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Client('invalid uri', 'http://www.example.com/');
    }

    public function testConstructorUriEmpty(): void
    {
        $this->expectException(TypeError::class);

        new Client(null, 'http://www.example.com/');
    }

    public function testConstructorUriPathUnspecified(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Client('ws://localhost', 'http://www.example.com/');
    }

    public function testConstructorOriginEmpty(): void
    {
        $this->expectException(TypeError::class);

        new Client('wss://localhost', null);
    }

    public function testConstructorOriginInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Client('ws://localhost:8000', 'NOTAVALIDURI');
    }

    public function testConnectWithResponseInParts(): void
    {
        $instance = $this->getInstanceWithResponse(static function (string $response): array {
            return ['', \substr($response, 0, 40), \substr($response, 40)];
        });

        self::assertTrue($instance->connect());
        self::assertSame([], $instance->receive());
    }

    public function testConnectWithPayloadAfterResponse(): void
    {
        $instance = $this->getInstanceWithResponse(static function (string $response): array {
            return [$response."\x81\x06foobar"];
        });

        self::assertTrue($instance->connect());
        self::assertTrue($instance->waitForData(0));

        $responses = $instance->receive();
        self::assertCount(1, $responses);
        self::assertSame('foobar', $responses[0]->getPayload());
    }

    public function testConnectDiscardsPayloadsOfPreviousConnection(): void
    {
        $payloads = ['foo', 'bar'];
        $instance = $this->getInstanceWithResponse(static function (string $response) use (&$payloads): array {
            return [$response."\x81\x03".\array_shift($payloads)];
        });

        self::assertTrue($instance->connect());
        self::assertTrue($instance->disconnect());
        self::assertTrue($instance->connect());

        $responses = $instance->receive();
        self::assertCount(1, $responses);
        self::assertSame('bar', $responses[0]->getPayload());
    }

    public function testSend(): void
    {
        try {
            $helper = new ServerTestHelper();
            $helper->setUp();

            /* @var \Wrench\Client */
            $instance = self::getInstance($helper->getEchoConnectionString(), 'http://www.example.com/send');
            $instance->addRequestHeader('X-Test', 'Custom Request Header');

            self::assertNull($instance->receive(), 'Receive before connect');
            self::assertNull($instance->waitForData(0), 'Wait for data before connect');

            $success = $instance->connect();
            self::assertTrue($success, 'Client can connect to test server');
            self::assertTrue($instance->isConnected());

            self::assertFalse($instance->connect(), 'Double connect');

            self::assertSame([], $instance->receive(), 'No data');

            $bytes = $instance->sendData('foobar', Protocol::TYPE_TEXT);
            self::assertTrue($bytes >= 6, 'sent text frame');

            $bytes = $instance->sendData('baz', Protocol::TYPE_TEXT);
            self::assertTrue($bytes >= 3, 'sent text frame');

            \usleep(500000);
            $responses = $instance->receive();
            self::assertIsArray($responses);
            self::assertCount(2, $responses);
            self::assertInstanceOf(Payload::class, $responses[0]);
            self::assertInstanceOf(Payload::class, $responses[1]);

            $bytes = $instance->sendData('baz', Protocol::TYPE_TEXT);
            self::assertTrue($bytes >= 3, 'sent text frame');
            self::assertSame([], $instance->receive(), 'Instantly receive after send');
            // test fix for issue #43
            $responses = $instance->receive(5.0);
            self::assertIsArray($responses);
            self::assertCount(1, $responses);
            self::assertInstanceOf(Payload::class, $responses[0]);

            $instance->disconnect();

            self::assertFalse($instance->isConnected());
            self::assertNull($instance->waitForData(0), 'Wait for data after disconnect');
        } finally {
            $helper->tearDown();
        }
    }
}
