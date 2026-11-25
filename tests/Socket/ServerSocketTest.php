<?php

namespace Wrench\Socket;

use Wrench\Test\ServerTestHelper;
use Wrench\Util\Ssl;

class ServerSocketTest extends UriSocketBaseTest
{
    public function testAccept(): void
    {
        $port = ServerTestHelper::getNextPort();
        $instance = self::getInstance('ws://localhost:'.$port);
        $instance->listen();

        $client = \stream_socket_client('tcp://localhost:'.$port);
        $accepted = $instance->accept();

        self::assertIsResource($accepted);
        self::assertGreaterThan(0, \socket_get_option(\socket_import_stream($accepted), \SOL_TCP, \TCP_NODELAY), 'Nagle algorithm is disabled');

        \fclose($client);
        \fclose($accepted);
        $instance->disconnect();
    }

    public function testAcceptReadsUnbuffered(): void
    {
        $port = ServerTestHelper::getNextPort();
        $instance = self::getInstance('ws://localhost:'.$port);
        $instance->listen();

        $client = \stream_socket_client('tcp://localhost:'.$port);
        $accepted = new ServerClientSocket($instance->accept());

        \fwrite($client, \str_repeat('a', 2 * AbstractSocket::DEFAULT_RECEIVE_LENGTH));

        self::assertTrue($accepted->waitForData(AbstractSocket::TIMEOUT_SOCKET));
        self::assertSame(AbstractSocket::DEFAULT_RECEIVE_LENGTH, \strlen($accepted->receive()));
        self::assertSame(AbstractSocket::DEFAULT_RECEIVE_LENGTH, \strlen($accepted->receive()));

        \fclose($client);
        $accepted->disconnect();
        $instance->disconnect();
    }

    public function testAcceptSecure(): void
    {
        $pem = \tempnam(\sys_get_temp_dir(), 'wrench');
        Ssl::generatePemFile($pem, null, 'GB', 'none', 'none', 'none', 'none', 'localhost', 'none@example.com');

        $port = ServerTestHelper::getNextPort();
        $instance = self::getInstance('wss://localhost:'.$port, ['server_ssl_cert_file' => $pem]);
        $instance->listen();

        // the TLS handshake needs both ends to run at once, so the client is another process
        $client = \proc_open(
            [\PHP_BINARY, '-r', \sprintf('$c = stream_context_create(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]); stream_socket_client("ssl://localhost:%d", $n, $s, 5, STREAM_CLIENT_CONNECT, $c);', $port)],
            [],
            $pipes
        );
        $accepted = $instance->accept();

        self::assertIsResource($accepted);

        \fclose($accepted);
        \proc_close($client);
        $instance->disconnect();
        \unlink($pem);
    }
}
