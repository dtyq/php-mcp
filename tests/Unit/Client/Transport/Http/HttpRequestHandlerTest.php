<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Transport\Http\HttpRequestHandler;
use Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test case for HttpRequestHandler.
 * @internal
 */
class HttpRequestHandlerTest extends TestCase
{
    private HttpConfig $config;

    /** @var LoggerProxy&MockObject */
    private $logger;

    /** @var AuthenticatorInterface&MockObject */
    private $authenticator;

    private HttpRequestHandler $handler;

    protected function setUp(): void
    {
        $this->config = new HttpConfig('https://api.example.com/mcp');
        $this->logger = $this->createMock(LoggerProxy::class);
        $this->authenticator = $this->createMock(AuthenticatorInterface::class);
        $this->handler = new HttpRequestHandler($this->config, $this->logger, $this->authenticator);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(HttpRequestHandler::class, $this->handler);
    }

    public function testGetAuthenticator(): void
    {
        $this->assertSame($this->authenticator, $this->handler->getAuthenticator());
    }

    public function testGetStats(): void
    {
        $stats = $this->handler->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('requests_sent', $stats);
        $this->assertArrayHasKey('requests_successful', $stats);
        $this->assertArrayHasKey('requests_failed', $stats);
        $this->assertArrayHasKey('total_retry_attempts', $stats);
        $this->assertArrayHasKey('average_response_time', $stats);
        $this->assertArrayHasKey('last_request_time', $stats);

        // Initial values
        $this->assertEquals(0, $stats['requests_sent']);
        $this->assertEquals(0, $stats['requests_successful']);
        $this->assertEquals(0, $stats['requests_failed']);
        $this->assertEquals(0, $stats['total_retry_attempts']);
        $this->assertEquals(0.0, $stats['average_response_time']);
        $this->assertNull($stats['last_request_time']);
    }

    public function testSendRequestThrowsTransportError(): void
    {
        // Since we can't easily mock HTTP requests without actual network calls,
        // we expect this to throw a TransportError due to network failure
        $this->expectException(TransportError::class);

        $message = '{"jsonrpc":"2.0","id":1,"method":"test"}';
        $sessionId = 'test-session-123';

        $this->handler->sendRequest($message, $sessionId);
    }

    public function testBuildHeaders(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('buildHeaders');
        $method->setAccessible(true);

        $sessionId = 'test-session-456';
        $headers = $method->invoke($this->handler, $sessionId);

        $this->assertIsArray($headers);
        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_CONTENT_TYPE, $headers);
        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_USER_AGENT, $headers);
        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_SESSION_ID, $headers);

        $this->assertEquals(ProtocolConstants::HTTP_CONTENT_TYPE_JSON, $headers[ProtocolConstants::HTTP_HEADER_CONTENT_TYPE]);
        $this->assertEquals($this->config->getUserAgent(), $headers[ProtocolConstants::HTTP_HEADER_USER_AGENT]);
        $this->assertEquals($sessionId, $headers[ProtocolConstants::HTTP_HEADER_SESSION_ID]);
    }

    public function testBuildHeadersWithoutSessionId(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('buildHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->handler, null);

        $this->assertIsArray($headers);
        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_CONTENT_TYPE, $headers);
        $this->assertArrayHasKey(ProtocolConstants::HTTP_HEADER_USER_AGENT, $headers);
        $this->assertArrayNotHasKey(ProtocolConstants::HTTP_HEADER_SESSION_ID, $headers);
    }

    public function testBuildHeadersWithCustomHeaders(): void
    {
        $customHeaders = ['X-Custom-Header' => 'custom-value', 'Authorization' => 'Bearer token'];
        $this->config->setHeaders($customHeaders);

        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('buildHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->handler, 'session-123');

        foreach ($customHeaders as $name => $value) {
            $this->assertArrayHasKey($name, $headers);
            $this->assertEquals($value, $headers[$name]);
        }
    }

    public function testBuildAuthHeaders(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('buildAuthHeaders');
        $method->setAccessible(true);

        $authHeaders = $method->invoke($this->handler);

        $this->assertIsArray($authHeaders);
        // Default should be empty since we have a mock authenticator with null auth config
    }

    public function testParseResponseHeaders(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('parseResponseHeaders');
        $method->setAccessible(true);

        $headerLines = [
            'HTTP/1.1 200 OK',
            'Content-Type: application/json',
            'Content-Length: 42',
            'Mcp-Session-Id: session-123',
        ];

        $parsed = $method->invoke($this->handler, $headerLines);

        $this->assertIsArray($parsed);
        $this->assertEquals(200, $parsed['status_code']);
        $this->assertEquals('application/json', $parsed['Content-Type']);
        $this->assertEquals('42', $parsed['Content-Length']);
        $this->assertEquals('session-123', $parsed['Mcp-Session-Id']);
    }

    public function testParseResponseHeadersWithInvalidStatusLine(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('parseResponseHeaders');
        $method->setAccessible(true);

        $headerLines = [
            'Invalid Status Line',
            'Content-Type: application/json',
        ];

        $parsed = $method->invoke($this->handler, $headerLines);

        $this->assertIsArray($parsed);
        $this->assertArrayNotHasKey('status_code', $parsed);
        $this->assertEquals('application/json', $parsed['Content-Type']);
    }

    public function testValidateResponse(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('validateResponse');
        $method->setAccessible(true);

        $headers = ['status_code' => 200];
        $body = '{"jsonrpc":"2.0","id":1,"result":{"success":true}}';

        // Should not throw exception for valid response
        $method->invoke($this->handler, $headers, $body);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testValidateResponseWithHttpError(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('validateResponse');
        $method->setAccessible(true);

        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('HTTP request failed with status 404');

        $headers = ['status_code' => 404];
        $body = 'Not Found';

        $method->invoke($this->handler, $headers, $body);
    }

    public function testValidateResponseWithEmptyBody(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('validateResponse');
        $method->setAccessible(true);

        // Empty body with status 200 is actually valid in this implementation
        $headers = ['status_code' => 200];
        $body = '';

        // Should not throw exception
        $method->invoke($this->handler, $headers, $body);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testValidateResponseWithInvalidJson(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('validateResponse');
        $method->setAccessible(true);

        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('Invalid JSON in response');

        $headers = ['status_code' => 200, 'Content-Type' => 'application/json'];
        $body = '{"invalid": json}';

        $method->invoke($this->handler, $headers, $body);
    }

    public function testConfigurationSettings(): void
    {
        $config = new HttpConfig('https://test.example.com/mcp', 60.0, 120.0, 5, 2.0);
        $handler = new HttpRequestHandler($config, $this->logger, $this->authenticator);

        $reflection = new ReflectionClass($handler);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $storedConfig = $configProperty->getValue($handler);

        $this->assertEquals('https://test.example.com/mcp', $storedConfig->getBaseUrl());
        $this->assertEquals(60.0, $storedConfig->getTimeout());
        $this->assertEquals(120.0, $storedConfig->getSseTimeout());
        $this->assertEquals(5, $storedConfig->getMaxRetries());
        $this->assertEquals(2.0, $storedConfig->getRetryDelay());
    }

    public function testInitializeStats(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('initializeStats');
        $method->setAccessible(true);

        $method->invoke($this->handler);

        $stats = $this->handler->getStats();
        $this->assertIsArray($stats);
        $this->assertEquals(0, $stats['requests_sent']);
        $this->assertEquals(0, $stats['requests_successful']);
        $this->assertEquals(0, $stats['requests_failed']);
        $this->assertEquals(0, $stats['total_retry_attempts']);
        $this->assertEquals(0.0, $stats['average_response_time']);
        $this->assertNull($stats['last_request_time']);
    }

    public function testUpdateStats(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('updateStats');
        $method->setAccessible(true);

        $method->invoke($this->handler, 'requests_successful');
        $method->invoke($this->handler, 'requests_successful');

        $stats = $this->handler->getStats();
        $this->assertEquals(2, $stats['requests_successful']);
    }

    public function testUpdateStatsWithValue(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('updateStats');
        $method->setAccessible(true);

        $method->invoke($this->handler, 'custom_value', 42);

        $stats = $this->handler->getStats();
        $this->assertEquals(42, $stats['custom_value']);
    }
}
