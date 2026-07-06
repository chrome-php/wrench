<?php

namespace Wrench;

use PHPUnit\Framework\ExpectationFailedException;
use Wrench\Application\DataHandlerInterface;
use Wrench\Exception\HandshakeException;
use Wrench\Protocol\Protocol;
use Wrench\Socket\AbstractSocket;
use Wrench\Socket\ServerClientSocket;
use Wrench\Test\BaseTest;

class ConnectionTest extends BaseTest
{
    /**
     * @dataProvider getValidConstructorOptions
     */
    public function testConstructor(array $options): void
    {
        $socket = $this->getMockSocket();

        $socket->expects($this->any())
            ->method('getIp')
            ->will($this->returnValue('127.0.0.1'));

        $socket->expects($this->any())
            ->method('getPort')
            ->will($this->returnValue(\random_int(1025, 50000)));

        $manager = $this->getMockConnectionManager();

        $this->assertInstanceOfClass(
            $instance = self::getInstance(
                $manager,
                $socket,
                $options
            ),
            'Valid constructor arguments'
        );
    }

    /**
     * @dataProvider getValidCloseCodes
     *
     * @doesNotPerformAssertions
     */
    public function testClose(int $code): void
    {
        $socket = $this->getMockSocket();

        $socket->expects($this->any())
            ->method('getIp')
            ->will($this->returnValue('127.0.0.1'));

        $socket->expects($this->any())
            ->method('getPort')
            ->will($this->returnValue(\random_int(1025, 50000)));

        $manager = $this->getMockConnectionManager();

        $connection = self::getInstance($manager, $socket);
        $connection->close($code);
    }

    private function getMockSocket(): ServerClientSocket
    {
        return $this->getMockBuilder(ServerClientSocket::class)
            ->onlyMethods(['getIp', 'getPort', 'isConnected', 'send'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider getValidHandshakeData
     */
    public function testHandshake(string $path, string $request): void
    {
        $connection = $this->getConnectionForHandshake(
            $this->getConnectedSocket(),
            $path,
            $request
        );

        $connection->handshake($request);

        $headers = $connection->getHeaders();
        self::assertEquals(\array_change_key_case(['X-Some-Header' => 'Some Value']), $headers, 'Extra headers returned');

        $params = $connection->getQueryParams();
        self::assertEquals(['someparam' => 'someval'], $params, 'Query string parameters returned');

        $connection->onData('somedata');
        self::assertTrue($connection->send('someotherdata'));
    }

    private function getConnectionForHandshake(ServerClientSocket $socket, string $path, string $request)
    {
        $manager = $this->getMockConnectionManager();

        $application = $this->getMockApplication();

        $server = $this->createMock(Server::class);
        $server->registerApplication($path, $application);

        $manager->expects($this->any())
            ->method('getApplicationForPath')
            ->with($path)
            ->will($this->returnValue($application));

        $manager->expects($this->any())
            ->method('getServer')
            ->will($this->returnValue($server));

        $connection = self::getInstance($manager, $socket);

        return $connection;
    }

    private function getMockApplication(): DataHandlerInterface
    {
        return $this->createMock(DataHandlerInterface::class);
    }

    private function getConnectedSocket(): ServerClientSocket
    {
        $socket = $this->getMockSocket();

        $socket->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));

        $socket->expects($this->any())
            ->method('send')
            ->will($this->returnValue(100));

        return $socket;
    }

    /**
     * @dataProvider getValidHandshakeData
     */
    public function testHandshakeBadSocket(string $path, string $request): void
    {
        $connection = $this->getConnectionForHandshake(
            $this->getNotConnectedSocket(),
            $path,
            $request
        );

        $this->expectException(HandshakeException::class);

        $connection->handshake($request);
    }

    private function getNotConnectedSocket(): AbstractSocket
    {
        $socket = $this->getMockSocket();

        $socket->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(false));

        return $socket;
    }

    /**
     * @dataProvider getWrongPathHandshakeData
     */
    public function testWrongPathHandshake(string $path, string $request): void
    {
        $connection = $this->getConnectionForHandshake(
            $this->getConnectedSocket(),
            $path,
            $request
        );

        // expectation is that only $path application is available
        $this->expectException(ExpectationFailedException::class);

        $connection->handshake($request);
    }

    /**
     * @dataProvider getValidHandleData
     */
    public function testHandle(string $path, string $handshake, array $requests, array $counts): void
    {
        $connection = $this->getConnectionForHandle(
            $this->getConnectedSocket(),
            $path,
            $handshake,
            $counts
        );

        $connection->handshake($handshake);

        foreach ($requests as $request) {
            $connection->handle($request);
        }
    }

    private function getConnectionForHandle(AbstractSocket $socket, string $path, string $handshake, array $counts): Connection
    {
        $connection = $this->getConnectionForHandshake($socket, $path, $handshake);

        $manager = $this->getMockConnectionManager();

        $application = $this->getMockApplication();

        $application->expects($this->exactly($counts['onData'] ?? 0))
            ->method('onData')
            ->will($this->returnValue(true));

        $server = $this->createMock(Server::class);
        $server->registerApplication($path, $application);

        $manager->expects($this->any())
            ->method('getApplicationForPath')
            ->with($path)
            ->will($this->returnValue($application));

        $manager->expects($this->exactly($counts['removeConnection'] ?? 0))
            ->method('removeConnection');

        $manager->expects($this->any())
            ->method('getServer')
            ->will($this->returnValue($server));

        $connection = self::getInstance($manager, $socket);

        return $connection;
    }

    /**
     * @return array<array<int>>
     */
    public static function getValidCloseCodes(): array
    {
        $arguments = [];
        foreach (Protocol::CLOSE_REASONS as $code => $reason) {
            $arguments[] = [$code];
        }

        return $arguments;
    }

    /**
     * @return array<array<mixed>>
     */
    public static function getValidConstructorOptions(): array
    {
        return [
            [
                [
                    'logger' => function (): void {
                    },
                ],
            ],
            [
                [
                    'logger' => function (): void {
                    },
                    'connection_id_algo' => 'sha512',
                ],
            ],
        ];
    }

    public static function getValidHandleData(): array
    {
        $validRequests = [
            [
                'data' => [
                    "\x81\xad\x2e\xab\x82\xac\x6f\xfe\xd6\xe4\x14\x8b\xf9\x8c\x0c"
                    ."\xde\xf1\xc9\x5c\xc5\xe3\xc1\x4b\x89\xb8\x8c\x0c\xcd\xed\xc3"
                    ."\x0c\x87\xa2\x8e\x5e\xca\xf1\xdf\x59\xc4\xf0\xc8\x0c\x91\xa2"
                    ."\x8e\x4c\xca\xf0\x8e\x53\x81\xad\xd4\xfd\x81\xfe\x95\xa8\xd5"
                    ."\xb6\xee\xdd\xfa\xde\xf6\x88\xf2\x9b\xa6\x93\xe0\x93\xb1\xdf"
                    ."\xbb\xde\xf6\x9b\xee\x91\xf6\xd1\xa1\xdc\xa4\x9c\xf2\x8d\xa3"
                    ."\x92\xf3\x9a\xf6\xc7\xa1\xdc\xb6\x9c\xf3\xdc\xa9\x81\x80\x8e"
                    ."\x12\xcd\x8e\x81\x8c\xf6\x8a\xf0\xee\x9a\xeb\x83\x9a\xd6\xe7"
                    ."\x95\x9d\x85\xeb\x97\x8b", // Four text frames
                ],
                'counts' => [
                    'onData' => 4,
                ],
            ],
            [
                'data' => [
                    "\x88\x80\xdc\x8e\xa2\xc5", // Close frame
                ],
                'counts' => [
                    'removeConnection' => 1,
                ],
            ],
        ];

        $data = [];

        $handshakes = self::getValidHandshakeData();

        foreach ($handshakes as $handshake) {
            foreach ($validRequests as $handleArgs) {
                $arguments = $handshake;
                $arguments[] = $handleArgs['data'];
                $arguments[] = $handleArgs['counts'];

                $data[] = $arguments;
            }
        }

        return $data;
    }

    public static function getValidHandshakeData(): array
    {
        return [
            [
                '/chat',
                "GET /chat?someparam=someval HTTP/1.1\r
Host: server.example.com\r
Upgrade: websocket\r
Connection: Upgrade\r
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r
X-Some-Header: Some Value\r
Origin: http://example.com\r
Sec-WebSocket-Version: 13\r\n\r\n",
            ],
        ];
    }

    public static function getWrongPathHandshakeData(): array
    {
        return [
            [
                '/foobar',
                "GET /chat HTTP/1.1\r
Host: server.example.com\r
Upgrade: websocket\r
Connection: Upgrade\r
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r
Origin: http://example.com\r
Sec-WebSocket-Version: 13\r\n\r\n",
            ],
        ];
    }
}
