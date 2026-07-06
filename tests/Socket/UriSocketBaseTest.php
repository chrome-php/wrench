<?php

namespace Wrench\Socket;

use InvalidArgumentException;

abstract class UriSocketBaseTest extends SocketBaseTest
{
    public function testConstructor(): void
    {
        $instance = self::getInstance('ws://localhost:8000');
        $this->assertInstanceOfClass($instance);
    }

    public function testIsConnected(): void
    {
        $instance = self::getInstance('ws://localhost:8000');
        $connected = $instance->isConnected();
        self::assertIsBool($connected, 'isConnected returns boolean');
        self::assertFalse($connected);
    }

    /**
     * @dataProvider getInvalidConstructorArguments
     */
    public function testInvalidConstructor($uri): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::getInstance($uri);
    }

    public function testGetIp(): void
    {
        $instance = self::getInstance('ws://localhost:8000');
        self::assertStringStartsWith('localhost', $instance->getIp(), 'Correct host');
    }

    public function testGetPort(): void
    {
        $instance = self::getInstance('ws://localhost:8000');
        self::assertEquals(8000, $instance->getPort(), 'Correct port');
    }

    public static function getInvalidConstructorArguments(): array
    {
        return [
            [false],
            ['http://www.google.com/'],
            ['ws:///'],
            [':::::'],
        ];
    }
}
