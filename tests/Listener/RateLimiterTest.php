<?php

namespace Wrench\Listener;

use Wrench\Connection;

class RateLimiterTest extends ListenerBaseTest
{
    public function testConstructor()
    {
        $instance = $this->getInstance();
        $this->assertInstanceOfClass($instance, 'No constructor arguments');

        return $instance;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testOnSocketConnect(): void
    {
        $this->getInstance()->onSocketConnect(null, $this->getConnection());
    }

    protected function getConnection()
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->any())
            ->method('getIp')
            ->will($this->returnValue('127.0.0.1'));

        $connection
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('abcdef01234567890'));

        $manager = $this->createMock('\Wrench\ConnectionManager');
        $manager->expects($this->any())->method('count')->will($this->returnValue(5));

        $connection
            ->expects($this->any())
            ->method('getConnectionManager')
            ->will($this->returnValue($manager));

        return $connection;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testOnSocketDisconnect(): void
    {
        $this->getInstance()->onSocketDisconnect(null, $this->getConnection());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testOnClientData(): void
    {
        $this->getInstance()->onClientData(null, $this->getConnection());
    }
}
