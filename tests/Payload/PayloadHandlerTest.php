<?php

namespace Wrench\Payload;

use Wrench\Test\BaseTest;

class PayloadHandlerTest extends BaseTest
{
    /**
     * @dataProvider getZeroPayloadChunks
     */
    public function testHandleZeroPayload(array $chunks): void
    {
        $received = [];
        $instance = self::getInstance(static function (Payload $payload) use (&$received): void {
            $received[] = $payload->getPayload();
        });

        foreach ($chunks as $chunk) {
            $instance->handle($chunk);
        }

        self::assertSame(['0', 'hello'], $received);
    }

    public static function getZeroPayloadChunks(): array
    {
        return [
            [["\x81\x010\x81\x05hello"]],
            [["\x81\x01", '0', "\x81\x05hello"]],
        ];
    }
}
