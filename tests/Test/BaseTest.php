<?php

namespace Wrench\Test;

use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Wrench\ConnectionManager;

abstract class BaseTest extends TestCase
{
    /**
     * Asserts that the given instance is of the class under test.
     */
    protected function assertInstanceOfClass($instance, $message = null): void
    {
        self::assertInstanceOf(
            static::getClass(),
            $instance,
            $message ?? ''
        );
    }

    /**
     * Gets the class under test.
     */
    protected static function getClass(): string
    {
        $class = static::class;

        if (\preg_match('/(.*)Test$/', $class, $matches)) {
            return $matches[1];
        }

        throw new LogicException('Cannot automatically determine class under test; configure manually by overriding getClass()');
    }

    /**
     * Gets an instance of the class under test.
     */
    protected static function getInstance(...$args): object
    {
        $reflection = new ReflectionClass(static::getClass());

        return $reflection->newInstanceArgs($args);
    }

    protected function getMockConnectionManager(): ConnectionManager
    {
        return $this->createMock(ConnectionManager::class);
    }
}
