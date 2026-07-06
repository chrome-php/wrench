<?php

namespace Wrench;

use Wrench\Application\DataHandlerInterface;
use Wrench\Test\BaseTest;

class ConnectionManagerTest extends BaseTest
{
    public function testValidConstructorArguments(): void
    {
        $this->assertInstanceOfClass(
            $instance = self::getInstance(
                $this->getMockServer(),
                []
            ),
            'Valid constructor arguments'
        );
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOfClass(
            self::getInstance(
                $this->getMockServer(),
                []
            ),
            'Constructor'
        );
    }

    private function getMockServer(): Server
    {
        $server = $this->createMock(Server::class);

        $server->registerApplication('/echo', self::getMockApplication());

        $server->expects($this->any())
            ->method('getUri')
            ->will($this->returnValue('ws://localhost:8000/'));

        return $server;
    }

    private static function getMockApplication(): DataHandlerInterface
    {
        return new class implements DataHandlerInterface {
            public function onData(string $data, Connection $connection): void
            {
                $connection->send($data);
            }
        };
    }

    public function testCount(): void
    {
        $connectionManager = self::getInstance(
            $this->getMockServer(),
            []
        );

        self::assertIsNumeric($connectionManager->count());
    }
}
