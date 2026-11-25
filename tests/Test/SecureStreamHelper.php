<?php

namespace Wrench\Test;

use RuntimeException;
use Wrench\Util\Ssl;

/**
 * A TLS connection inside a single process, whose encrypted bytes only
 * reach the client when the test delivers them.
 */
final class SecureStreamHelper
{
    private $pem;
    private $client;
    private $clientWire;
    private $server;
    private $serverWire;

    public function __construct()
    {
        $this->pem = \tempnam(\sys_get_temp_dir(), 'wrench');
        Ssl::generatePemFile($this->pem, null, 'GB', 'none', 'none', 'none', 'none', 'localhost', 'none@example.com');

        [$this->client, $this->clientWire] = self::createPair();
        [$this->serverWire, $this->server] = self::createPair();

        \stream_context_set_option($this->client, 'ssl', 'verify_peer', false);
        \stream_context_set_option($this->client, 'ssl', 'verify_peer_name', false);
        \stream_context_set_option($this->server, 'ssl', 'local_cert', $this->pem);

        \stream_set_blocking($this->client, false);
        \stream_set_blocking($this->server, false);

        $client = 0;
        $server = 0;

        do {
            if (true !== $client) {
                $client = \stream_socket_enable_crypto($this->client, true, \STREAM_CRYPTO_METHOD_TLS_CLIENT);
            }

            \fwrite($this->serverWire, self::read($this->clientWire));

            if (true !== $server) {
                $server = \stream_socket_enable_crypto($this->server, true, \STREAM_CRYPTO_METHOD_TLS_SERVER);
            }

            \fwrite($this->clientWire, self::read($this->serverWire));

            if (false === $client || false === $server) {
                throw new RuntimeException('TLS handshake failed');
            }
        } while (true !== $client || true !== $server);

        \stream_set_blocking($this->client, true);
    }

    public function __destruct()
    {
        foreach ([$this->client, $this->clientWire, $this->server, $this->serverWire] as $stream) {
            if (\is_resource($stream)) {
                \fclose($stream);
            }
        }

        \unlink($this->pem);
    }

    /**
     * @return resource
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Encrypts data on the server and returns the bytes it put on the wire.
     */
    public function encrypt(string $data): string
    {
        \fwrite($this->server, $data);

        return self::read($this->serverWire);
    }

    /**
     * Delivers bytes from the wire to the client.
     */
    public function deliver(string $bytes): void
    {
        \fwrite($this->clientWire, $bytes);

        // let the bytes cross the loopback interface
        \usleep(50000);
    }

    /**
     * Reads everything that arrives on a stream within a short time.
     *
     * @param resource $stream
     */
    private static function read($stream): string
    {
        $bytes = '';

        do {
            $read = [$stream];
            $write = null;
            $except = null;

            if (!\stream_select($read, $write, $except, 0, 100000)) {
                break;
            }

            $chunk = (string) \fread($stream, 65536);
            $bytes .= $chunk;
        } while ('' !== $chunk);

        return $bytes;
    }

    /**
     * @return array{resource, resource}
     */
    private static function createPair(): array
    {
        $listener = \stream_socket_server('tcp://127.0.0.1:0');
        $connecting = \stream_socket_client('tcp://'.\stream_socket_get_name($listener, false));
        $accepted = \stream_socket_accept($listener);
        \fclose($listener);

        return [$connecting, $accepted];
    }
}
