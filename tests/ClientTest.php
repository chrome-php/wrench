<?php

namespace Wrench;

use InvalidArgumentException;
use Wrench\Payload\Payload;
use Wrench\Protocol\Protocol;
use Wrench\Socket\ClientSocket;
use Wrench\Test\BaseTest;
use Wrench\Test\ServerTestHelper;

/**
 * Tests the client class.
 */
class ClientTest extends BaseTest
{
    public function testConstructor(): void
    {
        $this->assertInstanceOfClass(
            $client = new Client(
                'ws://localhost/test',
                'http://example.org/'
            ),
            'ws:// scheme, default socket'
        );

        $this->assertInstanceOfClass(
            $client = new Client(
                'ws://localhost/test',
                'http://example.org/',
                ['socket' => $this->getMockSocket()]
            ),
            'ws:// scheme, socket specified'
        );
    }

    /**
     * Gets a mock socket.
     *
     * @return \Wrench\Socket\Socket|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockSocket()
    {
        return $this->getMockBuilder(ClientSocket::class)
            ->setMethods(null)
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
        $this->expectException(InvalidArgumentException::class);

        new Client(null, 'http://www.example.com/');
    }

    public function testConstructorUriPathUnspecified(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Client('ws://localhost', 'http://www.example.com/');
    }

    public function testConstructorOriginEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

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

            /* @var $instance \Wrench\Client */
            $instance = $this->getInstance($helper->getEchoConnectionString(), 'http://www.example.com/send');
            $instance->addRequestHeader('X-Test', 'Custom Request Header');

            $this->assertNull($instance->receive(), 'Receive before connect');

            $success = $instance->connect();
            $this->assertTrue($success, 'Client can connect to test server');
            $this->assertTrue($instance->isConnected());

            $this->assertFalse($instance->connect(), 'Double connect');

            $this->assertFalse((bool) $instance->receive(), 'No data');

            $bytes = $instance->sendData('foobar', Protocol::TYPE_TEXT);
            $this->assertTrue($bytes >= 6, 'sent text frame');

            $bytes = $instance->sendData('baz', Protocol::TYPE_TEXT);
            $this->assertTrue($bytes >= 3, 'sent text frame');

            \usleep(500000);
            $responses = $instance->receive();
            $this->assertTrue(\is_array($responses));
            $this->assertCount(2, $responses);
            $this->assertInstanceOf(Payload::class, $responses[0]);
            $this->assertInstanceOf(Payload::class, $responses[1]);

            $bytes = $instance->sendData('baz', Protocol::TYPE_TEXT);
            $this->assertTrue($bytes >= 3, 'sent text frame');

            // test fix for issue #43
            $responses = $instance->receive();
            $this->assertTrue(\is_array($responses));
            $this->assertCount(1, $responses);
            $this->assertInstanceOf(Payload::class, $responses[0]);

            $instance->disconnect();

            $this->assertFalse($instance->isConnected());
        } finally {
            $helper->tearDown();
        }
    }
}
