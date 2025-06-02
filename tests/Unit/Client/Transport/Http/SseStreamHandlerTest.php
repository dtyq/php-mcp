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
use Dtyq\PhpMcp\Shared\Message\JsonRpcMessage;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for SseStreamHandler class.
 * @internal
 */
class SseStreamHandlerTest extends TestCase
{
    private SseStreamHandler $handler;

    private HttpConfig $config;

    private LoggerProxy $logger;

    protected function setUp(): void
    {
        $this->config = new HttpConfig('https://example.com');
        $this->logger = new LoggerProxy('test-sdk');
        $this->handler = new SseStreamHandler($this->config, $this->logger);
    }

    public function testConstruction(): void
    {
        $this->assertInstanceOf(SseStreamHandler::class, $this->handler);
        $this->assertFalse($this->handler->isConnected());
        $this->assertFalse($this->handler->isLegacyMode());
    }

    public function testSetEventCallback(): void
    {
        $called = false;
        $callback = function () use (&$called) {
            $called = true;
        };

        $this->handler->setEventCallback($callback);

        $stats = $this->handler->getStats();
        $this->assertTrue($stats['has_callback']);
    }

    public function testGetStats(): void
    {
        $stats = $this->handler->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('connected', $stats);
        $this->assertArrayHasKey('legacy_mode', $stats);
        $this->assertArrayHasKey('has_callback', $stats);
        $this->assertArrayHasKey('connection_timeout', $stats);
        $this->assertArrayHasKey('read_timeout_us', $stats);
        $this->assertArrayHasKey('stream_valid', $stats);

        $this->assertFalse($stats['connected']);
        $this->assertFalse($stats['legacy_mode']);
        $this->assertFalse($stats['has_callback']);
        $this->assertEquals(30, $stats['connection_timeout']);
        $this->assertEquals(100000, $stats['read_timeout_us']);
        $this->assertFalse($stats['stream_valid']);
    }

    public function testSetConnectionTimeout(): void
    {
        $this->handler->setConnectionTimeout(60);
        $stats = $this->handler->getStats();
        $this->assertEquals(60, $stats['connection_timeout']);
    }

    public function testSetConnectionTimeoutInvalid(): void
    {
        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('Connection timeout must be positive');

        $this->handler->setConnectionTimeout(0);
    }

    public function testSetReadTimeout(): void
    {
        $this->handler->setReadTimeout(200000);
        $stats = $this->handler->getStats();
        $this->assertEquals(200000, $stats['read_timeout_us']);
    }

    public function testSetReadTimeoutInvalid(): void
    {
        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('Read timeout must be positive');

        $this->handler->setReadTimeout(-1);
    }

    public function testDisconnect(): void
    {
        $this->handler->disconnect();
        $this->assertFalse($this->handler->isConnected());
        $this->assertFalse($this->handler->isLegacyMode());

        $stats = $this->handler->getStats();
        $this->assertFalse($stats['has_callback']);
    }

    public function testReceiveMessageWhenNotConnected(): void
    {
        $message = $this->handler->receiveMessage();
        $this->assertNull($message);
    }

    public function testMockSseEventParsing(): void
    {
        // Create a mock handler to test internal parsing methods
        $handler = new class($this->config, $this->logger) extends SseStreamHandler {
            /**
             * @param array<string, mixed> $event
             * @return array<string, string>
             */
            public function exposedParseEndpointEvent(array $event): array
            {
                return parent::parseEndpointEvent($event);
            }

            public function exposedParseJsonRpcMessage(string $data): ?JsonRpcMessage
            {
                return parent::parseJsonRpcMessage($data);
            }
        };

        // Test endpoint event parsing
        $endpointEvent = [
            'event' => 'endpoint',
            'data' => '{"uri": "https://example.com/post"}',
        ];

        $result = $handler->exposedParseEndpointEvent($endpointEvent);
        $this->assertEquals(['post_endpoint' => 'https://example.com/post'], $result);

        // Test invalid endpoint event
        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('Invalid endpoint event data format');

        $invalidEvent = ['event' => 'endpoint', 'data' => '{"invalid": "data"}'];
        $handler->exposedParseEndpointEvent($invalidEvent);
    }

    public function testJsonRpcMessageParsing(): void
    {
        $handler = new class($this->config, $this->logger) extends SseStreamHandler {
            public function exposedParseJsonRpcMessage(string $data): ?JsonRpcMessage
            {
                return parent::parseJsonRpcMessage($data);
            }
        };

        // Test valid JSON-RPC message
        $validData = json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'test',
            'params' => [],
        ]);

        $message = $handler->exposedParseJsonRpcMessage($validData);
        $this->assertInstanceOf(JsonRpcMessage::class, $message);

        // Test invalid JSON
        $invalidJson = '{"invalid": json}';
        $message = $handler->exposedParseJsonRpcMessage($invalidJson);
        $this->assertNull($message);

        // Test empty data
        $message = $handler->exposedParseJsonRpcMessage('');
        $this->assertNull($message);
    }

    public function testSseConnectionCreation(): void
    {
        // Create a testable handler that mocks the connection creation
        $handler = new class($this->config, $this->logger) extends SseStreamHandler {
            public bool $connectionAttempted = false;

            /** @var array<string, string> */
            public array $lastHeaders = [];

            public string $lastUrl = '';

            /**
             * @param array<string, string> $headers
             * @return resource
             */
            protected function createSseConnection(string $url, array $headers): mixed
            {
                $this->connectionAttempted = true;
                $this->lastUrl = $url;
                $this->lastHeaders = $headers;

                // Return a mock stream resource (using php://memory)
                $stream = fopen('php://memory', 'r+');
                if ($stream === false) {
                    throw new TransportError('Failed to create mock stream');
                }
                return $stream;
            }
        };

        // Test connection headers are properly set
        $handler->connectNew('https://example.com/sse', 'session-123');

        $this->assertTrue($handler->connectionAttempted);
        $this->assertEquals('https://example.com/sse', $handler->lastUrl);
        $this->assertArrayHasKey('Accept', $handler->lastHeaders);
        $this->assertArrayHasKey('Mcp-Session-Id', $handler->lastHeaders);
        $this->assertEquals('text/event-stream', $handler->lastHeaders['Accept']);
        $this->assertEquals('session-123', $handler->lastHeaders['Mcp-Session-Id']);
        $this->assertTrue($handler->isConnected());
        $this->assertFalse($handler->isLegacyMode());
    }

    public function testResumptionConnection(): void
    {
        $handler = new class($this->config, $this->logger) extends SseStreamHandler {
            public bool $connectionAttempted = false;

            /** @var array<string, string> */
            public array $lastHeaders = [];

            /**
             * @param array<string, string> $headers
             * @return resource
             */
            protected function createSseConnection(string $url, array $headers): mixed
            {
                $this->connectionAttempted = true;
                $this->lastHeaders = $headers;

                $stream = fopen('php://memory', 'r+');
                if ($stream === false) {
                    throw new TransportError('Failed to create mock stream');
                }
                return $stream;
            }
        };

        $resumptionHeaders = [
            'Last-Event-ID' => '12345',
            'Mcp-Session-Id' => 'session-456',
        ];

        $handler->connectWithResumption('https://example.com/sse', $resumptionHeaders);

        $this->assertTrue($handler->connectionAttempted);
        $this->assertArrayHasKey('Last-Event-ID', $handler->lastHeaders);
        $this->assertArrayHasKey('Accept', $handler->lastHeaders);
        $this->assertEquals('12345', $handler->lastHeaders['Last-Event-ID']);
        $this->assertEquals('text/event-stream', $handler->lastHeaders['Accept']);
        $this->assertTrue($handler->isConnected());
        $this->assertFalse($handler->isLegacyMode());
    }

    public function testLegacyConnectionWithEndpointEvent(): void
    {
        $handler = new class($this->config, $this->logger) extends SseStreamHandler {
            public bool $connectionAttempted = false;

            /** @var array<string, string> */
            public array $lastHeaders = [];

            /**
             * @param array<string, string> $headers
             * @return resource
             */
            protected function createSseConnection(string $url, array $headers): mixed
            {
                $this->connectionAttempted = true;

                $stream = fopen('php://memory', 'r+');
                if ($stream === false) {
                    throw new TransportError('Failed to create mock stream');
                }

                // Write SSE endpoint event to the stream
                $endpointData = 'event: endpoint' . "\n"
                               . 'data: {"uri": "https://example.com/mcp/post"}' . "\n"
                               . "\n";
                fwrite($stream, $endpointData);
                rewind($stream);

                return $stream;
            }

            /**
             * @return null|array<string, mixed>
             */
            protected function waitForEndpointEvent(): ?array
            {
                // Mock endpoint event
                return [
                    'event' => 'endpoint',
                    'data' => '{"uri": "https://example.com/mcp/post"}',
                ];
            }
        };

        $result = $handler->connectLegacy('https://example.com/sse');

        $this->assertTrue($handler->connectionAttempted);
        $this->assertTrue($handler->isConnected());
        $this->assertTrue($handler->isLegacyMode());
        $this->assertEquals(['post_endpoint' => 'https://example.com/mcp/post'], $result);
    }

    public function testLegacyConnectionWithoutEndpointEvent(): void
    {
        $handler = new class($this->config, $this->logger) extends SseStreamHandler {
            /** @var array<string, string> */
            public array $lastHeaders = [];

            /**
             * @param array<string, string> $headers
             * @return resource
             */
            protected function createSseConnection(string $url, array $headers): mixed
            {
                $stream = fopen('php://memory', 'r+');
                if ($stream === false) {
                    throw new TransportError('Failed to create mock stream');
                }
                return $stream;
            }

            /**
             * @return null|array<string, mixed>
             */
            protected function waitForEndpointEvent(): ?array
            {
                return null; // Simulate timeout
            }
        };

        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('Failed to receive expected endpoint event in legacy mode');

        $handler->connectLegacy('https://example.com/sse');
    }

    public function testEventCallbackInvocation(): void
    {
        $handler = new class($this->config, $this->logger) extends SseStreamHandler {
            public bool $callbackInvoked = false;

            public ?JsonRpcMessage $receivedMessage = null;

            public ?string $receivedEventId = null;

            public function forceConnected(): void
            {
                $reflection = new ReflectionClass(parent::class);
                $connectedProperty = $reflection->getProperty('connected');
                $connectedProperty->setAccessible(true);
                $connectedProperty->setValue($this, true);
            }

            /**
             * @return null|array<string, mixed>
             */
            protected function readSseEvent(): ?array
            {
                // Mock SSE event with ID
                return [
                    'event' => 'message',
                    'data' => '{"jsonrpc": "2.0", "id": 1, "method": "test", "params": []}',
                    'id' => 'event-123',
                ];
            }
        };

        // Set up callback
        $handler->setEventCallback(function (JsonRpcMessage $message, ?string $eventId) use ($handler) {
            $handler->callbackInvoked = true;
            $handler->receivedMessage = $message;
            $handler->receivedEventId = $eventId;
        });

        // Simulate connected state
        $handler->forceConnected();

        $message = $handler->receiveMessage();

        $this->assertInstanceOf(JsonRpcMessage::class, $message);
        $this->assertTrue($handler->callbackInvoked);
        $this->assertInstanceOf(JsonRpcMessage::class, $handler->receivedMessage);
        $this->assertEquals('event-123', $handler->receivedEventId);
    }

    public function testReceiveMessageInLegacyModeSkipsEndpointEvents(): void
    {
        $handler = new class($this->config, $this->logger) extends SseStreamHandler {
            private int $callCount = 0;

            public function forceConnectedAndLegacy(): void
            {
                $reflection = new ReflectionClass(parent::class);

                $connectedProperty = $reflection->getProperty('connected');
                $connectedProperty->setAccessible(true);
                $connectedProperty->setValue($this, true);

                $legacyProperty = $reflection->getProperty('isLegacyMode');
                $legacyProperty->setAccessible(true);
                $legacyProperty->setValue($this, true);
            }

            /**
             * @return null|array<string, mixed>
             */
            protected function readSseEvent(): ?array
            {
                ++$this->callCount;

                if ($this->callCount === 1) {
                    // First call returns endpoint event (should be skipped)
                    return [
                        'event' => 'endpoint',
                        'data' => '{"uri": "https://example.com/post"}',
                    ];
                }

                // Second call returns actual message
                return [
                    'event' => 'message',
                    'data' => '{"jsonrpc": "2.0", "id": 1, "method": "test", "params": []}',
                ];
            }
        };

        // Set up as legacy mode and connected
        $handler->forceConnectedAndLegacy();

        // First call should return null (endpoint event skipped)
        $message1 = $handler->receiveMessage();
        $this->assertNull($message1);

        // Second call should return the actual message
        $message2 = $handler->receiveMessage();
        $this->assertInstanceOf(JsonRpcMessage::class, $message2);
    }

    public function testConfigurationIntegration(): void
    {
        // Test with custom configuration
        $customConfig = new HttpConfig(
            'https://custom.example.com',
            sseTimeout: 120.0,
            validateSsl: false,
            userAgent: 'custom-sse-client/1.0',
            headers: ['X-Custom-Header' => 'custom-value']
        );

        $customHandler = new SseStreamHandler($customConfig, $this->logger);

        // Test that configuration is properly stored
        $this->assertInstanceOf(SseStreamHandler::class, $customHandler);
        $this->assertFalse($customHandler->isConnected());

        // Test timeout configuration through reflection
        $reflection = new ReflectionClass($customHandler);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($customHandler);

        $this->assertEquals(120.0, $config->getSseTimeout());
        $this->assertFalse($config->getValidateSsl());
        $this->assertEquals('custom-sse-client/1.0', $config->getUserAgent());
    }
}
