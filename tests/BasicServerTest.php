<?php

namespace Wrench;

use Psr\Log\NullLogger;

/**
 * Tests the BasicServer class.
 */
class BasicServerTest extends ServerTest
{
    /**
     * @param array  $allowed
     * @param string $origin
     * @dataProvider getValidOrigins
     */
    public function testValidOriginPolicy(array $allowed, $origin): void
    {
        $server = $this->getInstance('ws://localhost:8000', [
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
     * @param array  $allowed
     * @param string $origin
     * @dataProvider getInvalidOrigins
     */
    public function testInvalidOriginPolicy(array $allowed, $origin): void
    {
        $server = $this->getInstance('ws://localhost:8000', [
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
     * @see \Wrench\ServerTest::getValidConstructorArguments()
     */
    public function getValidConstructorArguments()
    {
        return \array_merge(parent::getValidConstructorArguments(), [
            [
                'ws://localhost:8000',
                ['logger' => new NullLogger()],
            ],
        ]);
    }

    /**
     * Data provider.
     *
     * @return array<array<mixed>>
     */
    public function getValidOrigins()
    {
        return [
            [['localhost'], 'localhost'],
            [['somewhere.com'], 'somewhere.com'],
        ];
    }

    /**
     * Data provider.
     *
     * @return array<array<mixed>>
     */
    public function getInvalidOrigins()
    {
        return [
            [['localhost'], 'blah'],
            [['somewhere.com'], 'somewhereelse.com'],
            [['somewhere.com'], 'subdomain.somewhere.com'],
        ];
    }
}
