<?php

namespace Wrench;

use Psr\Log\NullLogger;

class BasicServerTest extends ServerTest
{
    /**
     * @dataProvider getValidOrigins
     */
    public function testValidOriginPolicy(array $allowed, string $origin): void
    {
        $server = self::getInstance('ws://localhost:8000', [
            'allowed_origins' => $allowed,
            'logger' => new NullLogger(),
        ]);

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connection
            ->expects($this->never())
            ->method('close')
            ->will($this->returnValue(true));

        $server->notify(
            Server::EVENT_HANDSHAKE_REQUEST,
            [$connection, '', $origin, '', []]
        );
    }

    /**
     * @dataProvider getInvalidOrigins
     */
    public function testInvalidOriginPolicy(array $allowed, string $origin): void
    {
        $server = self::getInstance('ws://localhost:8000', [
            'allowed_origins' => $allowed,
            'logger' => new NullLogger(),
        ]);

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connection
            ->expects($this->atLeastOnce())
            ->method('close')
            ->will($this->returnValue(true));

        $server->notify(
            Server::EVENT_HANDSHAKE_REQUEST,
            [$connection, '', $origin, '', []]
        );
    }

    /**
     * @see ServerTest::getValidConstructorArguments()
     */
    public static function getValidConstructorArguments(): array
    {
        return \array_merge(parent::getValidConstructorArguments(), [
            [
                'ws://localhost:8000',
                ['logger' => new NullLogger()],
            ],
        ]);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function getValidOrigins(): array
    {
        return [
            [['localhost'], 'localhost'],
            [['somewhere.com'], 'somewhere.com'],
        ];
    }

    /**
     * @return array<array<mixed>>
     */
    public static function getInvalidOrigins(): array
    {
        return [
            [['localhost'], 'blah'],
            [['somewhere.com'], 'somewhereelse.com'],
            [['somewhere.com'], 'subdomain.somewhere.com'],
        ];
    }
}
