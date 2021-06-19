<?php

namespace Wrench\Socket;

use InvalidArgumentException;

abstract class UriSocketTest extends SocketBaseTest
{
    /**
     * By default, the socket has not required arguments.
     */
    public function testConstructor()
    {
        $instance = $this->getInstance('ws://localhost:8000');
        $this->assertInstanceOfClass($instance);

        return $instance;
    }

    /**
     * @dataProvider getInvalidConstructorArguments
     */
    public function testInvalidConstructor($uri): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getInstance($uri);
    }

    /**
     * @depends testConstructor
     */
    public function testGetIp($instance): void
    {
        $this->assertStringStartsWith('localhost', $instance->getIp(), 'Correct host');
    }

    /**
     * @depends testConstructor
     */
    public function testGetPort($instance): void
    {
        $this->assertEquals(8000, $instance->getPort(), 'Correct port');
    }

    /**
     * Data provider.
     */
    public function getInvalidConstructorArguments()
    {
        return [
            [false],
            ['http://www.google.com/'],
            ['ws:///'],
            [':::::'],
        ];
    }
}
