<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Integration\Client;

use PHPUnit\Framework\TestCase;

/**
 * Integration test to validate examples work correctly.
 *
 * This test ensures that the example client-server pairs work properly:
 * - stdio-client-test.php with stdio-server-test.php
 * - streamable-http-client-test.php with http-server-test.php
 *
 * @group integration
 * @group examples
 * @internal
 */
class ExamplesIntegrationTest extends TestCase
{
    private const HTTP_PORT = 8001; // Use different port to avoid conflicts

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure log directory exists
        $logDir = __DIR__ . '/../../../.log';
        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Test STDIO client-server communication using example files.
     */
    public function testStdioExampleIntegration(): void
    {
        $clientScript = __DIR__ . '/../../../examples/stdio-client-test.php';
        $serverScript = __DIR__ . '/../../../examples/stdio-server-test.php';

        // Verify files exist
        $this->assertFileExists($clientScript, 'stdio-client-test.php not found');
        $this->assertFileExists($serverScript, 'stdio-server-test.php not found');

        // Run client test (server is spawned internally via stdio transport)
        $output = [];
        $returnCode = 0;

        $command = 'cd ' . escapeshellarg(dirname($clientScript)) . ' && php ' . escapeshellarg(basename($clientScript)) . ' 2>&1';
        exec($command, $output, $returnCode);

        $outputText = implode("\n", $output);

        // Verify the test completed successfully
        $this->assertEquals(0, $returnCode, "STDIO example failed with return code {$returnCode}:\n{$outputText}");
        $this->assertStringContainsString('Demo completed successfully', $outputText, 'STDIO demo did not complete successfully');
        $this->assertStringContainsString('Connected and initialized', $outputText, 'STDIO connection failed');
        $this->assertStringContainsString('Available tools:', $outputText, 'Tools listing failed');
        $this->assertStringContainsString('Available prompts:', $outputText, 'Prompts listing failed');
        $this->assertStringContainsString('Available resources:', $outputText, 'Resources listing failed');
        $this->assertStringContainsString('Session closed', $outputText, 'Session cleanup failed');
    }

    /**
     * Test Streamable HTTP client-server communication using example files.
     */
    public function testStreamableHttpExampleIntegration(): void
    {
        $clientScript = __DIR__ . '/../../../examples/streamable-http-client-test.php';
        $serverScript = __DIR__ . '/../../../examples/http-server-test.php';

        // Verify files exist
        $this->assertFileExists($clientScript, 'streamable-http-client-test.php not found');
        $this->assertFileExists($serverScript, 'http-server-test.php not found');

        // Start HTTP server in background
        $serverProcess = $this->startHttpServer($serverScript);

        try {
            // Wait for server to start
            sleep(2);

            // Verify server is running
            $this->assertTrue($this->isServerRunning(), 'HTTP server failed to start');

            // Run client test with environment variable for port
            $output = [];
            $returnCode = 0;

            $env = 'TEST_HTTP_PORT=' . self::HTTP_PORT;
            $command = 'cd ' . escapeshellarg(dirname($clientScript)) . ' && ' . $env . ' php ' . escapeshellarg(basename($clientScript)) . ' 2>&1';
            exec($command, $output, $returnCode);

            $outputText = implode("\n", $output);

            // Verify the test completed successfully
            $this->assertEquals(0, $returnCode, "Streamable HTTP example failed with return code {$returnCode}:\n{$outputText}");
            $this->assertStringContainsString('All tests completed successfully', $outputText, 'Streamable HTTP test did not complete successfully');
            $this->assertStringContainsString('Connection successful', $outputText, 'HTTP connection failed');
            $this->assertStringContainsString('Session initialized', $outputText, 'Session initialization failed');
            $this->assertStringContainsString('Tool discovery', $outputText, 'Tool discovery failed');
            $this->assertStringContainsString('Prompt discovery', $outputText, 'Prompt discovery failed');
            $this->assertStringContainsString('Resource discovery', $outputText, 'Resource discovery failed');

            // Verify specific functionality
            $this->assertStringContainsString('Echo response:', $outputText, 'Echo tool test failed');
            $this->assertStringContainsString('Calculate response:', $outputText, 'Calculate tool test failed');
            $this->assertStringContainsString('Streamable info response:', $outputText, 'Streamable info tool test failed');
            $this->assertStringContainsString('Prompt response received:', $outputText, 'Prompt test failed');
            $this->assertStringContainsString('Resource data received', $outputText, 'Resource test failed');
            $this->assertStringContainsString('Template resource content received', $outputText, 'Resource template test failed');
        } finally {
            // Clean up server process
            $this->stopHttpServer($serverProcess);
        }
    }

    /**
     * Test that HTTP server responds to direct requests correctly.
     */
    public function testHttpServerDirectRequests(): void
    {
        $serverScript = __DIR__ . '/../../../examples/http-server-test.php';
        $this->assertFileExists($serverScript, 'http-server-test.php not found');

        // Start HTTP server in background
        $serverProcess = $this->startHttpServer($serverScript);

        try {
            // Wait for server to start
            sleep(2);
            $this->assertTrue($this->isServerRunning(), 'HTTP server failed to start');

            // Test basic server response
            $response = $this->makeHttpRequest('POST', '/mcp', [
                'Content-Type: application/json',
            ], json_encode([
                'jsonrpc' => '2.0',
                'method' => 'initialize',
                'params' => [
                    'protocolVersion' => '2025-03-26',
                    'capabilities' => ['tools' => []],
                ],
                'id' => 1,
            ]));

            $this->assertNotFalse($response, 'HTTP request failed');
            $this->assertStringContainsString('jsonrpc', $response, 'Invalid JSON-RPC response');
            $this->assertStringContainsString('Mcp-Session-Id', $response, 'Missing session ID header');

            // Test DELETE request for session termination
            $headers = $this->extractHeaders($response);
            $sessionId = $this->extractSessionId($headers);
            $this->assertNotNull($sessionId, 'Failed to extract session ID');

            $deleteResponse = $this->makeHttpRequest('DELETE', '/mcp', [
                "Mcp-Session-Id: {$sessionId}",
            ]);

            $this->assertNotFalse($deleteResponse, 'DELETE request failed');
            $this->assertStringContainsString('Session terminated successfully', $deleteResponse, 'Session termination failed');
        } finally {
            $this->stopHttpServer($serverProcess);
        }
    }

    /**
     * Start HTTP server process.
     */
    private function startHttpServer(string $serverScript): string
    {
        $command = sprintf(
            'php -S 127.0.0.1:%d %s > /dev/null 2>&1 & echo $!',
            self::HTTP_PORT,
            escapeshellarg($serverScript)
        );

        $process = popen($command, 'r');
        if ($process === false) {
            $this->fail('Failed to start HTTP server');
        }

        $pid = fgets($process);
        pclose($process);

        return trim($pid ?: '');
    }

    /**
     * Stop HTTP server process.
     */
    private function stopHttpServer(string $serverProcess): void
    {
        exec("kill {$serverProcess} 2>/dev/null");

        // Also kill any remaining PHP processes on our port
        exec('pkill -f "php -S 127.0.0.1:' . self::HTTP_PORT . '"');
    }

    /**
     * Check if HTTP server is running.
     */
    private function isServerRunning(): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:' . self::HTTP_PORT . '/mcp');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $result !== false && $httpCode > 0;
    }

    /**
     * Make HTTP request to test server.
     *
     * @param array<string> $headers
     * @return false|string
     */
    private function makeHttpRequest(string $method, string $path, array $headers = [], ?string $body = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:' . self::HTTP_PORT . $path);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Extract headers from HTTP response.
     *
     * @return array<string, string>
     */
    private function extractHeaders(string $response): array
    {
        $parts = explode("\r\n\r\n", $response, 2);
        $headerLines = explode("\r\n", $parts[0]);
        $headers = [];

        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }

        return $headers;
    }

    /**
     * Extract session ID from headers.
     *
     * @param array<string, string> $headers
     */
    private function extractSessionId(array $headers): ?string
    {
        return $headers['Mcp-Session-Id'] ?? null;
    }
}
