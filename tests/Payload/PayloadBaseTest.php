<?php

namespace Wrench\Payload;

use Wrench\Protocol\Protocol;
use Wrench\Socket\ClientSocket;
use Wrench\Test\BaseTest;

/**
 * Payload test.
 */
abstract class PayloadBaseTest extends BaseTest
{
    /**
     * A fresh instance of the class being tested.
     *
     * @var Payload
     */
    protected $payload;

    /**
     * @see PHPUnit_Payloadwork_TestCase::setUp()
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->payload = $this->getInstance();
    }

    /**
     * Tests the constructor.
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOfClass($this->getInstance());
    }

    /**
     * @param string $payload
     * @dataProvider getValidEncodePayloads
     */
    public function testBijection($type, $payload): void
    {
        // Encode the payload
        $this->payload->encode($payload, $type);

        // Create a new payload and read the data in with encode
        $payload = $this->getInstance();
        $payload->encode($this->payload->getPayload(), $type);

        // These still match
        $this->assertEquals(
            $this->payload->getType(),
            $payload->getType(),
            'Types match after encode -> receiveData'
        );

        $this->assertEquals(
            $this->payload->getPayload(),
            $payload->getPayload(),
            'Payloads match after encode -> receiveData'
        );
    }

    /**
     * @param string $payload
     * @dataProvider getValidEncodePayloads
     */
    public function testEncodeTypeReflection($type, $payload): void
    {
        $this->payload->encode($payload, Protocol::TYPE_TEXT);
        $this->assertEquals(Protocol::TYPE_TEXT, $this->payload->getType(), 'Encode retains type information');
    }

    /**
     * @param string $payload
     * @dataProvider getValidEncodePayloads
     */
    public function testEncodePayloadReflection($type, $payload): void
    {
        $this->payload->encode($payload, Protocol::TYPE_TEXT);
        $this->assertEquals($payload, $this->payload->getPayload(), 'Encode retains payload information');
    }

    /**
     * Tests sending to a socket.
     *
     * @dataProvider getValidEncodePayloads
     */
    public function testSendToSocket($type, $payload): void
    {
//        $successfulSocket = $this->getMockBuilder(ClientSocket::class)
//            ->setMethods(['send'])
//            ->setConstructorArgs(['wss://localhost:8000'])
//            ->getMock();

        $socket = $this->getMockBuilder(ClientSocket::class)
            ->setMethods(['getIp', 'getPort', 'isConnected', 'send'])
            ->disableOriginalConstructor()
            ->getMock();

        $failedSocket = $this->getMockBuilder(ClientSocket::class)
            ->setMethods(['getIp', 'getPort', 'isConnected', 'send'])
            ->disableOriginalConstructor()
            ->getMock();

        $socket->expects($this->any())
            ->method('send')
            ->will($this->returnValue(500));

        $failedSocket->expects($this->any())
            ->method('send')
            ->will($this->returnValue(null));

        $this->payload->encode($payload, $type);

        $this->assertTrue($this->payload->sendToSocket($socket));
        $this->assertFalse($this->payload->sendToSocket($failedSocket));
    }

    /**
     * Tests receiving data.
     *
     * @dataProvider getValidEncodePayloads
     * @doesNotPerformAssertions
     */
    public function testReceieveData($type, $payload): void
    {
        $payload = $this->getInstance();
        $payload->receiveData($payload);
    }

    /**
     * Data provider.
     *
     * @return array<string>
     */
    public function getValidEncodePayloads()
    {
        return [
            [
                Protocol::TYPE_TEXT,
                "123456\x007890!@#$%^&*()qwe\trtyuiopQWERTYUIOPasdfghjklASFGH\n
                JKLzxcvbnmZXCVBNM,./<>?;[]{}-=_+\|'asdad0x11\aasdassasdasasdsd",
            ],
            [
                Protocol::TYPE_TEXT,
                \pack('CCCCCCC', 0x00, 0x01, 0x02, 0x03, 0x04, 0xff, 0xf0),
            ],
            [Protocol::TYPE_TEXT, ' '],
        ];
    }
}
