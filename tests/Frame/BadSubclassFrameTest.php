<?php

namespace Wrench\Frame;

use Wrench\Exception\FrameException;
use Wrench\Test\BaseTest;

class BadSubclassFrameTest extends BaseTest
{
    public function testInvalidFrameBuffer(): void
    {
        $frame = new BadSubclassFrame();

        $this->expectException(FrameException::class);

        $frame->getFrameBuffer();
    }
}
