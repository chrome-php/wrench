<?php

namespace Wrench;

use InvalidArgumentException;
use TypeError;
use Wrench\Payload\Payload;
use Wrench\Protocol\Protocol;
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

    public function testSend(): void
    {
        try {
            $helper = new ServerTestHelper();
            $helper->setUp();

            /* @var \Wrench\Client */
            $instance = self::getInstance($helper->getEchoConnectionString(), 'http://www.example.com/send');
            $instance->addRequestHeader('X-Test', 'Custom Request Header');

            self::assertNull($instance->receive(), 'Receive before connect');

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
        } finally {
            $helper->tearDown();
        }
    }
}
