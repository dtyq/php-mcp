<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Transport\Http\SseStreamHandler;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test case for SseStreamHandler.
 * @internal
 */
class SseStreamHandlerTest extends TestCase
{
    private HttpConfig $config;

    /** @var LoggerProxy&MockObject */
    private $logger;

    private SseStreamHandler $handler;

    protected function setUp(): void
    {
        $this->config = new HttpConfig('https://api.example.com');
        $this->logger = $this->createMock(LoggerProxy::class);
        $this->handler = new SseStreamHandler($this->config, $this->logger);
    }

    protected function tearDown(): void
    {
        if ($this->handler->isConnected()) {
            $this->handler->disconnect();
        }
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(SseStreamHandler::class, $this->handler);
        $this->assertFalse($this->handler->isConnected());
    }

    public function testGetStatsInitial(): void
    {
        $stats = $this->handler->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('connections_established', $stats);
        $this->assertArrayHasKey('connection_errors', $stats);
        $this->assertArrayHasKey('connection_lost', $stats);
        $this->assertArrayHasKey('events_received', $stats);
        $this->assertArrayHasKey('read_errors', $stats);
        $this->assertArrayHasKey('last_event_time', $stats);
        $this->assertArrayHasKey('created_at', $stats);
        $this->assertArrayHasKey('connected', $stats);
        $this->assertArrayHasKey('session_id', $stats);
        $this->assertArrayHasKey('stream_alive', $stats);

        // Initial values
        $this->assertEquals(0, $stats['connections_established']);
        $this->assertEquals(0, $stats['connection_errors']);
        $this->assertEquals(0, $stats['connection_lost']);
        $this->assertEquals(0, $stats['events_received']);
        $this->assertEquals(0, $stats['read_errors']);
        $this->assertNull($stats['last_event_time']);
        $this->assertFalse($stats['connected']);
        $this->assertNull($stats['session_id']);
        $this->assertFalse($stats['stream_alive']);
        $this->assertIsFloat($stats['created_at']);
    }

    public function testIsConnectedInitially(): void
    {
        $this->assertFalse($this->handler->isConnected());
    }

    public function testDisconnectWhenNotConnected(): void
    {
        // Should not throw
        $this->handler->disconnect();
        $this->assertFalse($this->handler->isConnected());
    }

    public function testReadEventWhenNotConnected(): void
    {
        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('SSE stream not connected');

        $this->handler->readEvent();
    }

    public function testBuildHeaders(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('buildHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->handler);

        $this->assertIsArray($headers);
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertArrayHasKey('User-Agent', $headers);

        $this->assertEquals('text/event-stream', $headers['Accept']);
        $this->assertEquals('no-cache', $headers['Cache-Control']);
        $this->assertEquals($this->config->getUserAgent(), $headers['User-Agent']);
    }

    public function testBuildHeadersWithSessionId(): void
    {
        // Create a new handler and use reflection to set session ID
        $reflection = new ReflectionClass($this->handler);
        $sessionIdProperty = $reflection->getProperty('sessionId');
        $sessionIdProperty->setAccessible(true);
        $sessionIdProperty->setValue($this->handler, 'test-session-123');

        $method = $reflection->getMethod('buildHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->handler);

        $this->assertArrayHasKey('Mcp-Session-Id', $headers);
        $this->assertEquals('test-session-123', $headers['Mcp-Session-Id']);
    }

    public function testBuildHeadersWithCustomHeaders(): void
    {
        $customHeaders = ['X-Custom-Header' => 'custom-value', 'Authorization' => 'Bearer token'];
        $this->config->setHeaders($customHeaders);

        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('buildHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->handler);

        foreach ($customHeaders as $name => $value) {
            $this->assertArrayHasKey($name, $headers);
            $this->assertEquals($value, $headers[$name]);
        }
    }

    public function testFormatHeaders(): void
    {
        $headers = [
            'Accept' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'User-Agent' => 'test-agent',
        ];

        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('formatHeaders');
        $method->setAccessible(true);

        $formatted = $method->invoke($this->handler, $headers);

        $expected = "Accept: text/event-stream\r\nCache-Control: no-cache\r\nUser-Agent: test-agent";
        $this->assertEquals($expected, $formatted);
    }

    public function testIsStreamAliveWithoutStream(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('isStreamAlive');
        $method->setAccessible(true);

        $isAlive = $method->invoke($this->handler);
        $this->assertFalse($isAlive);
    }

    public function testInitializeStats(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('initializeStats');
        $method->setAccessible(true);

        $method->invoke($this->handler);

        $stats = $this->handler->getStats();
        $this->assertIsArray($stats);
        $this->assertEquals(0, $stats['connections_established']);
        $this->assertEquals(0, $stats['connection_errors']);
        $this->assertEquals(0, $stats['events_received']);
    }

    public function testUpdateStatsIncrement(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('updateStats');
        $method->setAccessible(true);

        $method->invoke($this->handler, 'events_received');
        $method->invoke($this->handler, 'events_received');

        $stats = $this->handler->getStats();
        $this->assertEquals(2, $stats['events_received']);
        $this->assertNotNull($stats['last_event_time']);
    }

    public function testUpdateStatsWithValue(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('updateStats');
        $method->setAccessible(true);

        $method->invoke($this->handler, 'connection_errors', 5);

        $stats = $this->handler->getStats();
        $this->assertEquals(5, $stats['connection_errors']);
    }

    public function testHandleConnectionLoss(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('handleConnectionLoss');
        $method->setAccessible(true);

        // Set connected to true first
        $connectedProperty = $reflection->getProperty('connected');
        $connectedProperty->setAccessible(true);
        $connectedProperty->setValue($this->handler, true);

        $method->invoke($this->handler);

        $this->assertFalse($this->handler->isConnected());

        $stats = $this->handler->getStats();
        $this->assertEquals(1, $stats['connection_lost']);
    }

    public function testConnectFailure(): void
    {
        // Use a config with short timeout to avoid long delays in tests
        $fastConfig = new HttpConfig(
            'https://api.example.com',
            1.0,  // timeout: 1 second
            1.0,  // sse_timeout: 1 second
            0     // max_retries: 0 (no retries)
        );
        $fastHandler = new SseStreamHandler($fastConfig, $this->logger);

        // This will fail because we're trying to connect to a real URL without a server
        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('SSE connection failed');

        $fastHandler->connect('test-session');
    }

    public function testConnectionTimeoutConfiguration(): void
    {
        $customConfig = new HttpConfig('https://api.example.com', 45.0, 600.0);
        $customHandler = new SseStreamHandler($customConfig, $this->logger);

        $reflection = new ReflectionClass($customHandler);
        $timeoutProperty = $reflection->getProperty('connectionTimeout');
        $timeoutProperty->setAccessible(true);
        $readTimeoutProperty = $reflection->getProperty('readTimeout');
        $readTimeoutProperty->setAccessible(true);

        $this->assertEquals(45, $timeoutProperty->getValue($customHandler));
        $this->assertEquals(600, $readTimeoutProperty->getValue($customHandler));
    }
}
