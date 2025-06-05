<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;

/**
 * HTTP connection manager for MCP transport.
 *
 * This class handles HTTP requests with retry logic, error handling,
 * and connection management. It supports various HTTP methods and
 * provides robust error handling with exponential backoff retry.
 */
class HttpConnectionManager
{
    private HttpConfig $config;

    private LoggerProxy $logger;

    /**
     * @param HttpConfig $config HTTP configuration
     * @param LoggerProxy $logger Logger instance
     */
    public function __construct(HttpConfig $config, LoggerProxy $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Send a POST request.
     *
     * @param string $url Target URL
     * @param array<string, string> $headers HTTP headers
     * @param null|array<string, mixed> $data Request body data (will be JSON encoded)
     * @return array<string, mixed> Response data
     * @throws TransportError If request fails
     */
    public function sendPostRequest(string $url, array $headers, ?array $data = null): array
    {
        return $this->sendRequest('POST', $url, $headers, $data);
    }

    /**
     * Send a GET request.
     *
     * @param string $url Target URL
     * @param array<string, string> $headers HTTP headers
     * @return array<string, mixed> Response data
     * @throws TransportError If request fails
     */
    public function sendGetRequest(string $url, array $headers): array
    {
        return $this->sendRequest('GET', $url, $headers);
    }

    /**
     * Send a DELETE request.
     *
     * @param string $url Target URL
     * @param array<string, string> $headers HTTP headers
     * @return array<string, mixed> Response data
     * @throws TransportError If request fails
     */
    public function sendDeleteRequest(string $url, array $headers): array
    {
        return $this->sendRequest('DELETE', $url, $headers);
    }

    /**
     * Get statistics about the connection manager.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return [
            'timeout' => $this->config->getTimeout(),
            'max_retries' => $this->config->getMaxRetries(),
            'retry_delay' => $this->config->getRetryDelay(),
            'validate_ssl' => $this->config->getValidateSsl(),
            'user_agent' => $this->config->getUserAgent(),
            'headers_count' => count($this->config->getHeaders()),
        ];
    }

    /**
     * Execute a single HTTP request.
     *
     * @param string $method HTTP method
     * @param string $url Target URL
     * @param array<string, string> $headers HTTP headers
     * @param null|array<string, mixed> $data Request body data
     * @return array<string, mixed> Response data
     * @throws TransportError If request execution fails
     */
    public function executeRequest(string $method, string $url, array $headers, ?array $data = null): array
    {
        $this->logger->debug('Executing HTTP request', [
            'method' => $method,
            'url' => $url,
            'headers_count' => count($headers),
            'has_data' => $data !== null,
        ]);

        $curlOptions = $this->buildCurlOptions($method, $url, $headers, $data);

        $ch = curl_init();
        if ($ch === false) {
            throw new TransportError('Failed to initialize cURL');
        }

        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        if ($response === false) {
            throw new TransportError('cURL execution failed: ' . $error);
        }

        if (! is_string($response)) {
            throw new TransportError('Invalid response type received');
        }

        // Parse response headers if available
        $responseHeaders = [];
        if (isset($info['header_size']) && $info['header_size'] > 0) {
            $headerString = substr($response, 0, $info['header_size']);
            $responseHeaders = $this->parseResponseHeaders($headerString);
            $body = substr($response, $info['header_size']);
        } else {
            $body = $response;
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'status_code' => $httpCode,
            'headers' => $responseHeaders,
            'body' => $body,
            'data' => $this->parseResponseBody($body),
            'info' => $info,
        ];
    }

    /**
     * Build CURL options for a request.
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array<string, string> $headers Request headers
     * @param null|array<string, mixed> $data Request data
     * @return array<int, mixed> CURL options array
     */
    protected function buildCurlOptions(string $method, string $url, array $headers, ?array $data = null): array
    {
        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => (int) $this->config->getTimeout(),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => $this->config->getValidateSsl(),
            CURLOPT_SSL_VERIFYHOST => $this->config->getValidateSsl() ? 2 : 0,
            CURLOPT_USERAGENT => $this->config->getUserAgent(),
            CURLOPT_HTTPHEADER => $this->buildHeaderArray($headers),
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        // Add request body for POST/PUT/PATCH requests
        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        // Merge custom headers from config
        $configHeaders = $this->config->getHeaders();
        if (! empty($configHeaders)) {
            $mergedHeaders = array_merge($headers, $configHeaders);
            $curlOptions[CURLOPT_HTTPHEADER] = $this->buildHeaderArray($mergedHeaders);
        }

        return $curlOptions;
    }

    /**
     * Convert associative array to cURL header format.
     *
     * @param array<string, string> $headers Headers array
     * @return array<int, string> Headers in cURL format
     */
    protected function buildHeaderArray(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = $key . ': ' . $value;
        }
        return $formatted;
    }

    /**
     * Parse response body as JSON.
     *
     * @param string $body Response body
     * @return null|array<string, mixed> Parsed data or null if not valid JSON
     */
    protected function parseResponseBody(string $body): ?array
    {
        if (empty($body)) {
            return null;
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->debug('Response body is not valid JSON', [
                'json_error' => json_last_error_msg(),
                'body_preview' => substr($body, 0, 200),
            ]);
            return null;
        }

        return $decoded;
    }

    /**
     * Parse HTTP response headers from header string.
     *
     * @param string $headerString Raw header string
     * @return array<string, string> Parsed headers
     */
    protected function parseResponseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'HTTP/') === 0) {
                continue;
            }

            $colonPos = strpos($line, ':');
            if ($colonPos !== false) {
                $name = trim(substr($line, 0, $colonPos));
                $value = trim(substr($line, $colonPos + 1));
                $headers[strtolower($name)] = $value;
            }
        }

        return $headers;
    }

    /**
     * Determine if an HTTP status code should trigger a retry.
     *
     * @param int $statusCode HTTP status code
     * @return bool True if should retry
     */
    protected function shouldRetry(int $statusCode): bool
    {
        // Retry on server errors, rate limiting, and timeout
        return in_array($statusCode, [
            408, // Request Timeout
            429, // Too Many Requests
            500, // Internal Server Error
            502, // Bad Gateway
            503, // Service Unavailable
            504, // Gateway Timeout
            507, // Insufficient Storage
            509, // Bandwidth Limit Exceeded
        ], true);
    }

    /**
     * Create appropriate TransportError for HTTP status code.
     *
     * @param int $statusCode HTTP status code
     * @param string $responseBody Response body
     * @return TransportError Transport error
     */
    protected function createHttpError(int $statusCode, string $responseBody): TransportError
    {
        $errorMessage = "HTTP error {$statusCode}";

        // Try to extract error message from response
        $errorData = $this->parseResponseBody($responseBody);
        if ($errorData && isset($errorData['error']['message'])) {
            $errorMessage .= ': ' . $errorData['error']['message'];
        } elseif ($errorData && isset($errorData['message'])) {
            $errorMessage .= ': ' . $errorData['message'];
        } elseif (! empty($responseBody)) {
            // Include a preview of the response body
            $preview = substr($responseBody, 0, 200);
            $errorMessage .= ': ' . $preview;
        }

        return new TransportError($errorMessage);
    }

    /**
     * Sleep for the specified duration.
     *
     * This method is separated to allow for easier testing by mocking.
     *
     * @param float $seconds Sleep duration in seconds
     */
    protected function sleep(float $seconds): void
    {
        usleep((int) ($seconds * 1000000));
    }

    /**
     * Send an HTTP request with retry logic.
     *
     * @param string $method HTTP method
     * @param string $url Target URL
     * @param array<string, string> $headers HTTP headers
     * @param null|array<string, mixed> $data Request body data
     * @return array<string, mixed> Response data
     * @throws TransportError If request fails after all retries
     */
    private function sendRequest(string $method, string $url, array $headers, ?array $data = null): array
    {
        // For DELETE requests, don't use retry logic - fail immediately
        if (strtoupper($method) === 'DELETE') {
            $response = $this->executeRequest($method, $url, $headers, $data);

            if ($response['status_code'] >= 400) {
                throw $this->createHttpError($response['status_code'], $response['body']);
            }

            $this->logger->debug('HTTP DELETE request successful', [
                'method' => $method,
                'url' => $url,
                'status_code' => $response['status_code'],
            ]);

            return $response;
        }

        $maxRetries = $this->config->getMaxRetries();
        $retryDelay = $this->config->getRetryDelay();

        for ($attempt = 0; $attempt <= $maxRetries; ++$attempt) {
            try {
                $response = $this->executeRequest($method, $url, $headers, $data);

                // Check if we should retry based on HTTP status code
                if ($response['status_code'] >= 400) {
                    if ($this->shouldRetry($response['status_code']) && $attempt < $maxRetries) {
                        $this->logger->warning('HTTP request failed, retrying', [
                            'attempt' => $attempt + 1,
                            'status_code' => $response['status_code'],
                            'url' => $url,
                            'method' => $method,
                            'delay' => $retryDelay,
                        ]);

                        $this->sleep($retryDelay);
                        $retryDelay *= 2; // Exponential backoff
                        continue;
                    }

                    throw $this->createHttpError($response['status_code'], $response['body']);
                }

                $this->logger->debug('HTTP request successful', [
                    'method' => $method,
                    'url' => $url,
                    'status_code' => $response['status_code'],
                    'attempt' => $attempt + 1,
                ]);

                return $response;
            } catch (TransportError $e) {
                // If it's the last attempt, re-throw the error
                if ($attempt === $maxRetries) {
                    throw $e;
                }

                $this->logger->warning('HTTP request failed, retrying', [
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage(),
                    'url' => $url,
                    'method' => $method,
                    'delay' => $retryDelay,
                ]);

                $this->sleep($retryDelay);
                $retryDelay *= 2; // Exponential backoff
            }
        }

        throw new TransportError('Request failed after maximum retries');
    }
}
