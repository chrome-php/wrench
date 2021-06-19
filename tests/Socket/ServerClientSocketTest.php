<?php

namespace Wrench\Socket;

use Wrench\Exception\SocketException;

class ServerClientSocketTest extends SocketBaseTest
{
    /**
     * By default, the socket has not required arguments.
     */
    public function testConstructor()
    {
        $instance = $this->getInstance(null);

        $this->assertInstanceOfClass($instance);

        return $instance;
    }

    /**
     * @depends testConstructor
     */
    public function testGetIpTooSoon($instance): void
    {
        $this->expectException(SocketException::class);

        $instance->getIp();
    }

    /**
     * @depends testConstructor
     */
    public function testGetPortTooSoon($instance): void
    {
        $this->expectException(SocketException::class);

        $instance->getPort();
    }
}
