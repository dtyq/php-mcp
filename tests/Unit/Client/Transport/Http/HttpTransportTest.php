<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Transport\Http\HttpTransport;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Test case for HttpTransport.
 * @internal
 */
class HttpTransportTest extends TestCase
{
    private HttpConfig $config;

    private Application $application;

    private HttpTransport $transport;

    protected function setUp(): void
    {
        $this->config = new HttpConfig('https://api.example.com');
        $this->application = $this->createMockApplication();
        $this->transport = new HttpTransport($this->config, $this->application);
    }

    protected function tearDown(): void
    {
        if ($this->transport->isConnected()) {
            $this->transport->disconnect();
        }
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(HttpTransport::class, $this->transport);
        $this->assertFalse($this->transport->isConnected());
    }

    public function testGetType(): void
    {
        $this->assertEquals(ProtocolConstants::TRANSPORT_TYPE_HTTP, $this->transport->getType());
    }

    public function testGetConfig(): void
    {
        $this->assertSame($this->config, $this->transport->getConfig());
    }

    public function testGetApplication(): void
    {
        $this->assertSame($this->application, $this->transport->getApplication());
    }

    public function testGetSessionIdWhenNotConnected(): void
    {
        $this->assertNull($this->transport->getSessionId());
    }

    public function testIsConnectedInitially(): void
    {
        $this->assertFalse($this->transport->isConnected());
    }

    public function testGetStats(): void
    {
        $stats = $this->transport->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('transport_type', $stats);
        $this->assertArrayHasKey('created_at', $stats);
        $this->assertArrayHasKey('connection_attempts', $stats);
        $this->assertArrayHasKey('messages_sent', $stats);
        $this->assertArrayHasKey('messages_received', $stats);
        $this->assertArrayHasKey('errors', $stats);
        $this->assertArrayHasKey('connected', $stats);
        $this->assertArrayHasKey('session_id', $stats);
        $this->assertArrayHasKey('base_url', $stats);

        $this->assertEquals(ProtocolConstants::TRANSPORT_TYPE_HTTP, $stats['transport_type']);
        $this->assertEquals(0, $stats['connection_attempts']);
        $this->assertEquals(0, $stats['messages_sent']);
        $this->assertEquals(0, $stats['messages_received']);
        $this->assertEquals(0, $stats['errors']);
        $this->assertFalse($stats['connected']);
        $this->assertNull($stats['session_id']);
        $this->assertEquals($this->config->getBaseUrl(), $stats['base_url']);
    }

    public function testDisconnectWhenNotConnected(): void
    {
        // Should not throw
        $this->transport->disconnect();
        $this->assertFalse($this->transport->isConnected());
    }

    private function createMockApplication(): Application
    {
        $container = new class implements ContainerInterface {
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

        return new Application($container, [
            'sdk_name' => 'test-http-transport',
        ]);
    }
}
