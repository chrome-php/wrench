<?php

namespace Wrench\Protocol;

use Exception;
use InvalidArgumentException;
use Wrench\Test\BaseTest;

abstract class ProtocolBaseTest extends BaseTest
{
    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider getValidHandshakeRequests
     */
    public function testValidatHandshakeRequestValid(string $request): void
    {
        try {
            [$path, $origin, $key, $extensions, $protocol] = self::getInstance()->validateRequestHandshake($request);

            self::assertEquals('/chat', $path);
            self::assertEquals('http://example.com', $origin);
            self::assertEquals('dGhlIHNhbXBsZSBub25jZQ==', $key);
            self::assertTrue(\is_array($extensions), 'Extensions returned as array');
            self::assertEquals(['x-test', 'x-test2'], $extensions, 'Extensions match');
            self::assertEquals('chat, superchat', $protocol);
        } catch (Exception $e) {
            $this->fail($e);
        }
    }

    /**
     * @dataProvider getValidHandshakeResponses
     */
    public function testValidateHandshakeResponseValid(string $response, string $key): void
    {
        try {
            $valid = self::getInstance()->validateResponseHandshake($response, $key);
            self::assertTrue(\is_bool($valid), 'Validation return value is boolean');
            self::assertTrue($valid, 'Handshake response validates');
        } catch (Exception $e) {
            $this->fail('Validated valid response handshake as invalid');
        }
    }

    /**
     * @dataProvider getValidHandshakeResponses
     */
    public function testGetResponseHandsake(string $response, string $key): void
    {
        try {
            $response = self::getInstance()->getResponseHandshake($key);
            self::assertHttpResponse($response);
        } catch (Exception $e) {
            $this->fail('Unable to get handshake response: '.$e);
        }
    }

    protected function assertHttpResponse(string $response, string $message = ''): void
    {
        self::assertStringStartsWith('HTTP', $response, $message.' - response starts well');
        self::assertStringEndsWith("\r\n", $response, $message.' - response ends well');
    }

    public function testGetVersion(): void
    {
        $version = self::getInstance()->getVersion();
        self::assertTrue(\is_int($version));
    }

    public function testGetResponseError(): void
    {
        $response = self::getInstance()->getResponseError(400);
        self::assertHttpResponse($response, 'Code as int');

        $response = self::getInstance()->getResponseError(new Exception('Some message', 500));
        self::assertHttpResponse($response, 'Code in Exception');

        $response = self::getInstance()->getResponseError(888);
        self::assertHttpResponse($response, 'Invalid code produces unimplemented response');
    }

    /**
     * @dataProvider getValidOriginUris
     *
     * @doesNotPerformAssertions
     */
    public function testValidateOriginUriValid(string $uri): void
    {
        try {
            $valid = self::getInstance()->validateOriginUri($uri);
        } catch (Exception $e) {
            $this->fail('Valid URI validated as invalid: '.$e);
        }
    }

    /**
     * @dataProvider getInvalidOriginUris
     */
    public function testValidateOriginUriInvalid($uri): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::getInstance()->validateOriginUri($uri);
    }

    public static function getValidOriginUris(): array
    {
        return [
            ['http://www.example.org'],
            ['http://www.example.com/some/page'],
            ['https://localhost/'],
        ];
    }

    public static function getInvalidOriginUris(): array
    {
        return [
            [false],
            [true],
            [''],
            ['blah'],
        ];
    }

    public static function getValidHandshakeRequests(): array
    {
        $cases = [];

        $cases[] = ["GET /chat HTTP/1.1\r
Host: server.example.com\r
Upgrade: websocket\r
Connection: Upgrade\r
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r
Origin: http://example.com\r
Sec-WebSocket-Extensions: x-test\r
Sec-WebSocket-Extensions: x-test2\r
Sec-WebSocket-Protocol: chat, superchat\r
Sec-WebSocket-Version: 13\r
\r\n"];

        $cases[] = ["GET /chat HTTP/1.1\r
Host: server.example.com\r
Upgrade: Websocket\r
Connection: Upgrade\r
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r
Origin: http://example.com\r
Sec-WebSocket-Extensions: x-test\r
Sec-WebSocket-Extensions: x-test2\r
Sec-WebSocket-Protocol: chat, superchat\r
Sec-WebSocket-Version: 13\r
\r\n"];

        return $cases;
    }

    public static function getValidHandshakeResponses(): array
    {
        $cases = [];

        for ($i = 10; $i > 0; --$i) {
            $key = \sha1(\time().\uniqid('', true));
            $response = "HTTP/1.1 101 WebSocket Protocol Handshake\r\nUpgrade: WebSocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: "
                .\base64_encode(\sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true))
                ."\r\n\r\n";

            $cases[] = [$response, $key];
        }

        return $cases;
    }
}
