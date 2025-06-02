<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Transport\Http\HttpConnectionManager;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HttpConnectionManager class.
 * @internal
 */
class HttpConnectionManagerTest extends TestCase
{
    private HttpConnectionManager $connectionManager;

    private HttpConfig $config;

    private LoggerProxy $logger;

    protected function setUp(): void
    {
        $this->config = new HttpConfig('https://httpbin.org');
        $this->logger = new LoggerProxy('test-sdk');
        $this->connectionManager = new HttpConnectionManager($this->config, $this->logger);
    }

    public function testGetStats(): void
    {
        $stats = $this->connectionManager->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('timeout', $stats);
        $this->assertArrayHasKey('max_retries', $stats);
        $this->assertArrayHasKey('retry_delay', $stats);
        $this->assertArrayHasKey('validate_ssl', $stats);
        $this->assertArrayHasKey('user_agent', $stats);
        $this->assertArrayHasKey('headers_count', $stats);

        $this->assertEquals(30.0, $stats['timeout']);
        $this->assertEquals(3, $stats['max_retries']);
        $this->assertEquals(1.0, $stats['retry_delay']);
        $this->assertTrue($stats['validate_ssl']);
        $this->assertEquals('php-mcp-client/1.0', $stats['user_agent']);
        $this->assertEquals(0, $stats['headers_count']);
    }

    public function testBuildCurlOptions(): void
    {
        // Create a testable version of HttpConnectionManager that exposes protected methods
        $connectionManager = new class($this->config, $this->logger) extends HttpConnectionManager {
            /**
             * @param array<string, string> $headers
             * @param null|array<string, mixed> $data
             * @return array<int, mixed>
             */
            public function exposedBuildCurlOptions(string $method, string $url, array $headers, ?array $data = null): array
            {
                return parent::buildCurlOptions($method, $url, $headers, $data);
            }
        };

        $options = $connectionManager->exposedBuildCurlOptions(
            'POST',
            'https://httpbin.org/post',
            ['Content-Type' => 'application/json'],
            ['test' => 'data']
        );

        $this->assertIsArray($options);
        $this->assertEquals('https://httpbin.org/post', $options[CURLOPT_URL]);
        $this->assertTrue($options[CURLOPT_RETURNTRANSFER]);
        $this->assertEquals('POST', $options[CURLOPT_CUSTOMREQUEST]);
        $this->assertEquals(30, $options[CURLOPT_TIMEOUT]);
        $this->assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertEquals('php-mcp-client/1.0', $options[CURLOPT_USERAGENT]);
        $this->assertEquals('{"test":"data"}', $options[CURLOPT_POSTFIELDS]);
        $this->assertContains('Content-Type: application/json', $options[CURLOPT_HTTPHEADER]);
    }

    public function testBuildHeaderArray(): void
    {
        $connectionManager = new class($this->config, $this->logger) extends HttpConnectionManager {
            /**
             * @param array<string, string> $headers
             * @return array<int, string>
             */
            public function exposedBuildHeaderArray(array $headers): array
            {
                return parent::buildHeaderArray($headers);
            }
        };

        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer token'];
        $formatted = $connectionManager->exposedBuildHeaderArray($headers);

        $this->assertIsArray($formatted);
        $this->assertContains('Content-Type: application/json', $formatted);
        $this->assertContains('Authorization: Bearer token', $formatted);
    }

    public function testParseResponseBody(): void
    {
        $connectionManager = new class($this->config, $this->logger) extends HttpConnectionManager {
            /**
             * @return null|array<string, mixed>
             */
            public function exposedParseResponseBody(string $body): ?array
            {
                return parent::parseResponseBody($body);
            }
        };

        // Test valid JSON
        $validJson = '{"success": true, "data": {"id": 123}}';
        $parsed = $connectionManager->exposedParseResponseBody($validJson);
        $this->assertIsArray($parsed);
        $this->assertTrue($parsed['success']);
        $this->assertEquals(123, $parsed['data']['id']);

        // Test invalid JSON
        $invalidJson = '{"invalid": json}';
        $parsed = $connectionManager->exposedParseResponseBody($invalidJson);
        $this->assertNull($parsed);

        // Test empty body
        $parsed = $connectionManager->exposedParseResponseBody('');
        $this->assertNull($parsed);
    }

    public function testShouldRetry(): void
    {
        $connectionManager = new class($this->config, $this->logger) extends HttpConnectionManager {
            public function exposedShouldRetry(int $statusCode): bool
            {
                return parent::shouldRetry($statusCode);
            }
        };

        // Should retry
        $this->assertTrue($connectionManager->exposedShouldRetry(500));
        $this->assertTrue($connectionManager->exposedShouldRetry(502));
        $this->assertTrue($connectionManager->exposedShouldRetry(503));
        $this->assertTrue($connectionManager->exposedShouldRetry(504));
        $this->assertTrue($connectionManager->exposedShouldRetry(429));
        $this->assertTrue($connectionManager->exposedShouldRetry(408));

        // Should not retry
        $this->assertFalse($connectionManager->exposedShouldRetry(200));
        $this->assertFalse($connectionManager->exposedShouldRetry(400));
        $this->assertFalse($connectionManager->exposedShouldRetry(401));
        $this->assertFalse($connectionManager->exposedShouldRetry(403));
        $this->assertFalse($connectionManager->exposedShouldRetry(404));
    }

    public function testCreateHttpError(): void
    {
        $connectionManager = new class($this->config, $this->logger) extends HttpConnectionManager {
            public function exposedCreateHttpError(int $statusCode, string $responseBody): TransportError
            {
                return parent::createHttpError($statusCode, $responseBody);
            }
        };

        // Test with JSON error response
        $jsonError = '{"error": {"message": "Not found"}}';
        $error = $connectionManager->exposedCreateHttpError(404, $jsonError);
        $this->assertInstanceOf(TransportError::class, $error);
        $this->assertStringContainsString('HTTP error 404: Not found', $error->getMessage());

        // Test with simple message
        $simpleError = '{"message": "Validation failed"}';
        $error = $connectionManager->exposedCreateHttpError(400, $simpleError);
        $this->assertStringContainsString('HTTP error 400: Validation failed', $error->getMessage());

        // Test with plain text response
        $plainError = 'Internal server error occurred';
        $error = $connectionManager->exposedCreateHttpError(500, $plainError);
        $this->assertStringContainsString('HTTP error 500: Internal server error occurred', $error->getMessage());
    }

    public function testSleepMethod(): void
    {
        $connectionManager = new class($this->config, $this->logger) extends HttpConnectionManager {
            public bool $sleepCalled = false;

            public float $sleepDuration = 0;

            protected function sleep(float $seconds): void
            {
                $this->sleepCalled = true;
                $this->sleepDuration = $seconds;
                // Don't actually sleep in tests
            }

            public function exposedSleep(float $seconds): void
            {
                $this->sleep($seconds);
            }
        };

        $connectionManager->exposedSleep(0.5);
        $this->assertTrue($connectionManager->sleepCalled);
        $this->assertEquals(0.5, $connectionManager->sleepDuration);
    }

    public function testConfigurationIntegration(): void
    {
        // Test with custom configuration using positional parameters for PHP 7.4 compatibility
        $customConfig = new HttpConfig(
            'https://httpbin.org',  // baseUrl
            60.0,  // timeout
            300.0, // sseTimeout
            5,     // maxRetries
            2.0,   // retryDelay
            false, // validateSsl
            'custom-agent/1.0', // userAgent
            ['X-Custom-Header' => 'custom-value'], // headers
            null,  // auth
            'auto' // protocolVersion
        );

        $manager = new HttpConnectionManager($customConfig, $this->logger);
        $stats = $manager->getStats();

        $this->assertEquals(60.0, $stats['timeout']);
        $this->assertEquals(5, $stats['max_retries']);
        $this->assertEquals(2.0, $stats['retry_delay']);
        $this->assertFalse($stats['validate_ssl']);
        $this->assertEquals('custom-agent/1.0', $stats['user_agent']);
        $this->assertEquals(1, $stats['headers_count']);
    }

    /**
     * Test HTTP methods with actual network calls (can be skipped if no internet).
     * Optimized to reduce test time.
     */
    public function testHttpMethodsWithHttpBin(): void
    {
        if (! $this->isNetworkAvailable()) {
            $this->markTestSkipped('Network not available for HTTP tests');
        }

        try {
            // Test GET request
            $response = $this->connectionManager->sendGetRequest(
                'https://httpbin.org/get',
                ['Accept' => 'application/json']
            );
            $this->assertTrue($response['success']);
            $this->assertEquals(200, $response['status_code']);
            $this->assertIsArray($response['data']);

            // Test POST request
            $postData = ['test' => 'data', 'number' => 123];
            $response = $this->connectionManager->sendPostRequest(
                'https://httpbin.org/post',
                ['Content-Type' => 'application/json'],
                $postData
            );
            $this->assertTrue($response['success']);
            $this->assertEquals(200, $response['status_code']);
            $this->assertIsArray($response['data']);
            $this->assertEquals($postData, $response['data']['json']);

            // Test DELETE request
            $response = $this->connectionManager->sendDeleteRequest(
                'https://httpbin.org/delete',
                ['Accept' => 'application/json']
            );
            $this->assertTrue($response['success']);
            $this->assertEquals(200, $response['status_code']);
        } catch (TransportError $e) {
            $this->markTestSkipped('HTTP test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test retry mechanism with mock.
     */
    public function testRetryMechanism(): void
    {
        // Use positional parameters for PHP 7.4 compatibility
        $config = new HttpConfig(
            'https://httpbin.org', // baseUrl
            30.0,  // timeout
            300.0, // sseTimeout
            2,     // maxRetries
            0.1,   // retryDelay
            true,  // validateSsl
            'php-mcp-client/1.0', // userAgent
            [],    // headers
            null,  // auth
            'auto' // protocolVersion
        );

        $manager = new class($config, $this->logger) extends HttpConnectionManager {
            public int $attempts = 0;

            /**
             * @param array<string, string> $headers
             * @param null|array<string, mixed> $data
             * @return array<string, mixed>
             */
            protected function executeRequest(string $method, string $url, array $headers, ?array $data = null): array
            {
                ++$this->attempts;

                // Simulate server error on first two attempts, success on third
                if ($this->attempts <= 2) {
                    return [
                        'success' => false,
                        'status_code' => 503,
                        'body' => 'Service Unavailable',
                        'data' => null,
                        'info' => [],
                    ];
                }

                return [
                    'success' => true,
                    'status_code' => 200,
                    'body' => '{"success": true}',
                    'data' => ['success' => true],
                    'info' => [],
                ];
            }

            protected function sleep(float $seconds): void
            {
                // Don't actually sleep in tests
            }
        };

        $response = $manager->sendGetRequest('https://example.com', []);
        $this->assertTrue($response['success']);
        $this->assertEquals(3, $manager->attempts);
    }

    /**
     * Test maximum retries exceeded.
     */
    public function testMaxRetriesExceeded(): void
    {
        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('HTTP error 503');

        // Use positional parameters for PHP 7.4 compatibility
        $config = new HttpConfig(
            'https://httpbin.org', // baseUrl
            30.0,  // timeout
            300.0, // sseTimeout
            1,     // maxRetries
            0.1,   // retryDelay
            true,  // validateSsl
            'php-mcp-client/1.0', // userAgent
            [],    // headers
            null,  // auth
            'auto' // protocolVersion
        );

        $manager = new class($config, $this->logger) extends HttpConnectionManager {
            /**
             * @param array<string, string> $headers
             * @param null|array<string, mixed> $data
             * @return array<string, mixed>
             */
            protected function executeRequest(string $method, string $url, array $headers, ?array $data = null): array
            {
                return [
                    'success' => false,
                    'status_code' => 503,
                    'body' => 'Service Unavailable',
                    'data' => null,
                    'info' => [],
                ];
            }

            protected function sleep(float $seconds): void
            {
                // Don't actually sleep in tests
            }
        };

        $manager->sendGetRequest('https://example.com', []);
    }

    /**
     * Check if network is available for testing.
     * Optimized with shorter timeout to reduce wait time.
     */
    private function isNetworkAvailable(): bool
    {
        return @fsockopen('httpbin.org', 80, $errno, $errstr, 1) !== false; // Reduced timeout from 5 to 1 second
    }
}
