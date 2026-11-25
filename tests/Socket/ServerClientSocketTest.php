<?php

namespace Wrench\Socket;

use Wrench\Exception\SocketException;
use Wrench\Test\SecureStreamHelper;

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

    public function testGetLastErrorTooSoon(): void
    {
        $instance = self::getInstance(null);

        self::assertSame('Not connected', $instance->getLastError());
    }

    public function testGetLastError(): void
    {
        [$local, $remote] = \stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        $instance = self::getInstance($local);

        self::assertSame('Unknown error', $instance->getLastError());

        \fclose($remote);
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

    public function testReceiveReadsOnceFromUnbufferedSocket(): void
    {
        [$local, $remote] = \stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        \stream_set_read_buffer($local, 0);
        \fwrite($remote, \str_repeat('a', 2 * AbstractSocket::DEFAULT_RECEIVE_LENGTH));

        $instance = self::getInstance($local);

        self::assertSame(AbstractSocket::DEFAULT_RECEIVE_LENGTH, \strlen($instance->receive()));
        self::assertSame(AbstractSocket::DEFAULT_RECEIVE_LENGTH, \strlen($instance->receive()));

        \fclose($remote);
    }

    public function testReceiveWaitsForDataToArrive(): void
    {
        [$local, $remote] = \stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        $instance = self::getInstance($local);

        $start = \microtime(true);
        $received = $instance->receive(AbstractSocket::DEFAULT_RECEIVE_LENGTH, 0.2);

        self::assertSame('', $received);
        self::assertGreaterThan(0.1, \microtime(true) - $start, 'receive waited for data to arrive');

        \fclose($remote);
    }

    public function testReceiveDoesNotWaitWhenDataIsAvailable(): void
    {
        [$local, $remote] = \stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        \fwrite($remote, 'foobar');

        $instance = self::getInstance($local);

        $start = \microtime(true);
        $received = $instance->receive(AbstractSocket::DEFAULT_RECEIVE_LENGTH, 5.0);

        self::assertSame('foobar', $received);
        self::assertLessThan(2, \microtime(true) - $start, 'receive returned as soon as data was available');

        \fclose($remote);
    }

    public function testReceiveDisconnectsWhenClosed(): void
    {
        [$local, $remote] = \stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        $instance = self::getInstance($local);

        \fclose($remote);

        self::assertSame('', $instance->receive());
        self::assertFalse($instance->isConnected());
    }

    public function testReceiveReturnsSecureDataAlreadyAvailable(): void
    {
        $helper = new SecureStreamHelper();
        $helper->deliver($helper->encrypt('foobar'));

        $instance = self::getInstance($helper->getClient());

        $start = \microtime(true);
        $received = $instance->receive();

        self::assertSame('foobar', $received);
        self::assertLessThan(2, \microtime(true) - $start, 'receive did not block on a secure socket');
    }

    public function testReceiveIgnoresIncompleteSecureRecord(): void
    {
        $helper = new SecureStreamHelper();
        $bytes = $helper->encrypt('foobar');

        $instance = self::getInstance($helper->getClient());

        $helper->deliver(\substr($bytes, 0, 10));

        self::assertSame('', $instance->receive());
        self::assertTrue($instance->isConnected());

        $helper->deliver(\substr($bytes, 10));

        self::assertSame('foobar', $instance->receive());
    }

    public function testReceiveWaitsPastIncompleteSecureRecord(): void
    {
        $helper = new SecureStreamHelper();
        $bytes = $helper->encrypt('foobar');

        $instance = self::getInstance($helper->getClient());

        $helper->deliver(\substr($bytes, 0, 10));

        $start = \microtime(true);
        $received = $instance->receive(AbstractSocket::DEFAULT_RECEIVE_LENGTH, 0.2);

        $elapsed = \microtime(true) - $start;

        self::assertSame('', $received);
        self::assertGreaterThan(0.1, $elapsed, 'receive kept waiting for the rest of the record');
        self::assertLessThan(2, $elapsed, 'receive stopped waiting when the time ran out');
    }
}
