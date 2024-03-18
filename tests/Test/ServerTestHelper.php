<?php

namespace Wrench\Test;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * In conjunction with server.php, provides a listening server
 * against which tests can be run.
 */
final class ServerTestHelper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const TEST_SERVER_PORT_MIN = 16666;
    public const TEST_SERVER_PORT_MAX = 52222;

    public static $nextPort = null;

    private $port = null;
    private $process = null;
    private $pipes = [];

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function __destruct()
    {
        $this->tearDown();
    }

    /**
     * Tears down the server process.
     */
    public function tearDown(): void
    {
        if ($this->process) {
            foreach ($this->pipes as &$pipe) {
                \fclose($pipe);
            }
            $this->pipes = null;

            // Sigh
            $status = \proc_get_status($this->process);

            if ($status && isset($status['pid']) && $status['pid']) {
                // More sigh, this is the pid of the parent sh process, we want
                //  to terminate the server directly
                $this->logger->info('Command: ps -ao pid,ppid | col | tail -n +2 | grep \'  '
                    .$status['pid']
                    ."'");
                \exec('ps -ao pid,ppid | col | tail -n +2 | grep \' '
                    .$status['pid']
                    ."'", $processes, $return);

                if (0 === $return) {
                    foreach ($processes as $process) {
                        [$pid, $ppid] = \explode(' ', \str_replace('  ', ' ', $process));
                        if ($pid) {
                            $this->logger->info('Killing '.$pid);
                            \exec('kill '.$pid.' > /dev/null 2>&1');
                        }
                    }
                } else {
                    $this->logger->warning('Unable to find child processes');
                }

                \sleep(1);

                $this->logger->info('Killing '.$status['pid']);
                \exec('kill '.$status['pid'].' > /dev/null 2>&1');

                \sleep(1);
            }

            \proc_close($this->process);
            $this->process = null;
        }
    }

    public function getEchoConnectionString(): string
    {
        return $this->getConnectionString().'/echo';
    }

    public function getConnectionString(): string
    {
        return 'ws://localhost:'.$this->port;
    }

    /**
     * Sets up the server process and sleeps for a few seconds while
     * it wakes up.
     */
    public function setUp(): void
    {
        $this->port = self::getNextPort();

        $directory = \sprintf('%s/%s', \sys_get_temp_dir(), \bin2hex(\random_bytes(16)));
        \mkdir($directory);

        $this->process = \proc_open(
            $this->getCommand(),
            [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', $directory.'/server.log', 'a+'],
                2 => ['file', $directory.'/server.err.log', 'a+'],
            ],
            $this->pipes,
            __DIR__.'/../'
        );
        if (false === $this->process) {
            throw new \RuntimeException('proc_open failed: '.\var_export(\error_get_last(), true));
        }
        \sleep(3);
    }

    /**
     * Gets the next available port number to start a server on.
     */
    public static function getNextPort(): int
    {
        if (null === self::$nextPort) {
            self::$nextPort = \mt_rand(self::TEST_SERVER_PORT_MIN, self::TEST_SERVER_PORT_MAX);
        }

        return self::$nextPort++;
    }

    /**
     * Gets the server command.
     */
    private function getCommand(): string
    {
        return \sprintf('/usr/bin/env php %s %d', \escapeshellarg(__DIR__.'/server.php'), $this->port);
    }
}
