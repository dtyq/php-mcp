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
use Dtyq\PhpMcp\Shared\Utilities\SSE\SSEEvent;
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
        $this->assertArrayHasKey('has_sse_client', $stats);

        $this->assertFalse($stats['connected']);
        $this->assertFalse($stats['legacy_mode']);
        $this->assertFalse($stats['has_callback']);
        $this->assertEquals(30, $stats['connection_timeout']);
        $this->assertEquals(100000, $stats['read_timeout_us']);
        $this->assertFalse($stats['has_sse_client']);
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
             * @return array<string, string>
             */
            public function exposedParseEndpointEvent(SSEEvent $event): array
            {
                return parent::parseEndpointEvent($event);
            }

            public function exposedParseJsonRpcMessage(string $data): ?JsonRpcMessage
            {
                return parent::parseJsonRpcMessage($data);
            }
        };

        // Test endpoint event parsing
        $endpointEvent = new SSEEvent(
            '',
            'endpoint',
            '{"uri": "https://example.com/post"}',
            0
        );

        $result = $handler->exposedParseEndpointEvent($endpointEvent);
        $this->assertEquals(['post_endpoint' => 'https://example.com/post'], $result);

        // Test invalid endpoint event
        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('Invalid endpoint event data format');

        $invalidEvent = new SSEEvent(
            '',
            'endpoint',
            '{"invalid": "data"}',
            0
        );
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

    public function testConfigurationIntegration(): void
    {
        // Test with custom configuration
        $customConfig = new HttpConfig(
            'https://example.com',
            15.0,                           // timeout
            120.0,                          // sseTimeout
            3, // maxRetries
            1.0,                            // retryDelay
            false,                          // validateSsl
            'custom-sse-client/1.0',        // userAgent
            ['X-Custom-Header' => 'custom-value']  // headers
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
