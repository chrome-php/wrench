<?php

namespace Wrench\Socket;

use Wrench\Exception\SocketException;

class ServerClientSocketTest extends SocketBaseTest
{
    public function testConstructor(): void
    {
        $instance = self::getInstance(null);
        $this->assertInstanceOfClass($instance);
    }

    public function testIsConnected(): void
    {
        $instance = self::getInstance(null);
        $connected = $instance->isConnected();
        self::assertIsBool($connected, 'isConnected returns boolean');
        self::assertFalse($connected);
    }

    public function testGetIpTooSoon(): void
    {
        $instance = self::getInstance(null);
        $this->expectException(SocketException::class);

        $instance->getIp();
    }

    public function testGetPortTooSoon(): void
    {
        $instance = self::getInstance(null);
        $this->expectException(SocketException::class);

        $instance->getPort();
    }

    public function testReceiveReturnsImmediatelyWhenNoDataIsAvailable(): void
    {
        [$local, $remote] = \stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        $instance = self::getInstance($local);

        $start = \microtime(true);
        $received = $instance->receive();

        self::assertSame('', $received);
        self::assertLessThan(2, \microtime(true) - $start, 'receive did not block on an empty socket');

        \fclose($remote);
    }

    public function testReceiveReturnsDataAlreadyAvailable(): void
    {
        [$local, $remote] = \stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        $sent = \str_repeat('a', 2 * AbstractSocket::DEFAULT_RECEIVE_LENGTH);
        \fwrite($remote, $sent);

        $instance = self::getInstance($local);

        self::assertSame($sent, $instance->receive());

        \fclose($remote);
    }
}
