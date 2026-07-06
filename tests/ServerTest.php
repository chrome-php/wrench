<?php

namespace Wrench;

use Wrench\Test\BaseTest;
use Wrench\Util\LoopInterface;

class ServerTest extends BaseTest
{
    /**
     * @dataProvider getValidConstructorArguments
     */
    public function testConstructor(string $url, array $options = []): void
    {
        $this->assertInstanceOfClass(
            self::getInstance($url, $options),
            'Valid constructor arguments'
        );
    }

    /**
     * @return array<array<mixed>>
     */
    public static function getValidConstructorArguments(): array
    {
        return [
            [
                'ws://localhost:8000',
                [],
            ],
            [
                'ws://localhost',
            ],
        ];
    }

    public function testLoop(): void
    {
        /**
         * A simple loop that only runs 5 times.
         */
        $countLoop = new class implements LoopInterface {
            public $count = 0;

            public function shouldContinue(): bool
            {
                return $this->count++ < 5;
            }
        };

        $c = $this->getMockConnectionManager();

        $c->expects($this->exactly(5))
            ->method('selectAndProcess');

        $server = new Server('ws://localhost:8000', [
            'connection_manager' => $c,
        ]);
        $server->setLoop($countLoop);
        $server->run();
    }
}
