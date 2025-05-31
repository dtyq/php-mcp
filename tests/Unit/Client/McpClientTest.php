<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client;

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Test case for McpClient.
 * @internal
 */
class McpClientTest extends TestCase
{
    private Application $application;

    private McpClient $client;

    protected function setUp(): void
    {
        $container = $this->createMockContainer();
        $this->application = new Application($container, [
            'sdk_name' => 'test-client',
        ]);

        $this->client = new McpClient('test-client', '1.0.0', $this->application);
    }

    protected function tearDown(): void
    {
        $this->client->close();
    }

    public function testConstructor(): void
    {
        $client = new McpClient('test-client', '1.0.0', $this->application);

        $this->assertInstanceOf(McpClient::class, $client);
        $this->assertEquals(0, $client->getSessionCount());
    }

    public function testConnectWithInvalidTransportType(): void
    {
        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('Unsupported transport type');

        $this->client->connect('invalid', []);
    }

    public function testConnectStdioWithoutCommand(): void
    {
        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('command');

        $this->client->connect('stdio', []);
    }

    public function testConnectStdioWithInvalidCommand(): void
    {
        $this->markTestSkipped('Test requires actual process execution which may vary by environment');

        $this->expectException(TransportError::class);

        $this->client->connect('stdio', [
            'command' => ['non-existent-command-12345'],
        ]);
    }

    public function testGetSessionWithInvalidId(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Session not found');

        $this->client->getSession('invalid-session-id');
    }

    public function testHasSession(): void
    {
        $this->assertFalse($this->client->hasSession('non-existent'));
    }

    public function testGetSessionIds(): void
    {
        $sessionIds = $this->client->getSessionIds();
        $this->assertIsArray($sessionIds);
        $this->assertEmpty($sessionIds);
    }

    public function testGetSessionCount(): void
    {
        $this->assertEquals(0, $this->client->getSessionCount());
    }

    public function testRemoveNonExistentSession(): void
    {
        $result = $this->client->removeSession('non-existent');
        $this->assertFalse($result);
    }

    public function testGetStats(): void
    {
        $stats = $this->client->getStats();

        $this->assertIsObject($stats);
        $this->assertEquals(0, $stats->getConnectionAttempts());
        $this->assertEquals(0, $stats->getConnectionErrors());
    }

    public function testClose(): void
    {
        // Should not throw any exceptions
        $this->client->close();

        // Should be safe to call multiple times
        $this->client->close();

        $this->addToAssertionCount(1);
    }

    private function createMockContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            /** @var array<string, object> */
            private array $services = [];

            public function __construct()
            {
                $this->services[LoggerInterface::class] = new NullLogger();
                $this->services[EventDispatcherInterface::class] = new class implements EventDispatcherInterface {
                    public function dispatch(object $event): object
                    {
                        return $event;
                    }
                };
            }

            public function get($id)
            {
                return $this->services[$id] ?? null;
            }

            public function has($id): bool
            {
                return isset($this->services[$id]);
            }
        };
    }
}
