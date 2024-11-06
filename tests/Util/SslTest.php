<?php

namespace Wrench\Test;

use Wrench\Util\Ssl;

class SslTest extends BaseTest
{
    private $tmp;

    public function setUp(): void
    {
        parent::setUp();

        $this->tmp = \tempnam('/tmp', 'wrench');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if ($this->tmp) {
            @\unlink($this->tmp);
        }
    }

    public function testGeneratePemWithPassphrase(): void
    {
        Ssl::generatePemFile(
            $this->tmp,
            'password',
            'nz',
            'Somewhere',
            'Over the rainbow',
            'Birds fly, inc.',
            'Over the rainbow division',
            '127.0.0.1',
            'nobody@example.com'
        );

        self::assertFileExists($this->tmp);

        $contents = \file_get_contents($this->tmp);

        self::assertMatchesRegularExpression('/BEGIN CERTIFICATE/', $contents, 'PEM file contains certificate');
        self::assertMatchesRegularExpression('/BEGIN ENCRYPTED PRIVATE KEY/', $contents, 'PEM file contains encrypted private key');
    }

    public function testGeneratePemWithoutPassphrase(): void
    {
        Ssl::generatePemFile(
            $this->tmp,
            null,
            'de',
            'Somewhere',
            'Over the rainbow',
            'Birds fly, inc.',
            'Over the rainbow division',
            '127.0.0.1',
            'nobody@example.com'
        );

        self::assertFileExists($this->tmp);

        $contents = \file_get_contents($this->tmp);

        self::assertMatchesRegularExpression('/BEGIN CERTIFICATE/', $contents, 'PEM file contains certificate');
        self::assertMatchesRegularExpression('/BEGIN PRIVATE KEY/', $contents, 'PEM file contains encrypted private key');
    }
}
