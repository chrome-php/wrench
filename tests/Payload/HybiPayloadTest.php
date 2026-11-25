<?php

namespace Wrench\Payload;

class HybiPayloadTest extends PayloadBaseTest
{
    public function testReceiveDataWithZeroPayload(): void
    {
        $payload = self::getInstance();
        $payload->receiveData("\x81\x010");

        self::assertTrue($payload->isComplete());
        self::assertSame('0', $payload->getPayload());
    }
}
