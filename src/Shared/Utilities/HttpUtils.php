<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Utilities;

/**
 * Utilities for HTTP operations and URL manipulation.
 *
 * This class provides common HTTP utilities used throughout the MCP codebase,
 * similar to Python SDK's _httpx_utils.py functionality.
 */
class HttpUtils
{
    /**
     * Default timeout in seconds.
     */
    public const DEFAULT_TIMEOUT = 30.0;

    /**
     * HTTP methods constants.
     */
    public const METHOD_GET = 'GET';

    public const METHOD_POST = 'POST';

    public const METHOD_PUT = 'PUT';

    public const METHOD_DELETE = 'DELETE';

    public const METHOD_PATCH = 'PATCH';

    public const METHOD_HEAD = 'HEAD';

    public const METHOD_OPTIONS = 'OPTIONS';

    /**
     * Common HTTP headers for MCP operations.
     */
    public const HEADERS_JSON = ['Content-Type' => 'application/json'];

    public const HEADERS_FORM = ['Content-Type' => 'application/x-www-form-urlencoded'];

    public const HEADERS_SSE = [
        'Accept' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
    ];

    public const HEADERS_STREAMABLE_HTTP = [
        'Content-Type' => 'application/json',
        'Accept' => 'text/event-stream, application/json',
    ];

    /**
     * Create HTTP context options with MCP defaults.
     *
     * @param string $method HTTP method
     * @param string[] $headers HTTP headers
     * @param string $content Request body content
     * @param float $timeout Request timeout in seconds
     * @return array<string, mixed> HTTP context options for stream_context_create()
     */
    public static function createHttpContext(
        string $method = self::METHOD_GET,
        array $headers = [],
        string $content = '',
        float $timeout = self::DEFAULT_TIMEOUT
    ): array {
        // Build headers string
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = "{$name}: {$value}";
        }

        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headerLines),
                'timeout' => $timeout,
                'follow_location' => true,
                'max_redirects' => 5,
            ],
        ];

        if (! empty($content)) {
            $options['http']['content'] = $content;
        }

        return $options;
    }

    /**
     * Create HTTP context for JSON requests.
     *
     * @param string $method HTTP method
     * @param mixed $data Data to JSON encode
     * @param string[] $additionalHeaders Additional headers
     * @param float $timeout Request timeout in seconds
     * @return array<string, mixed> HTTP context options
     */
    public static function createJsonContext(
        string $method,
        $data = null,
        array $additionalHeaders = [],
        float $timeout = self::DEFAULT_TIMEOUT
    ): array {
        $headers = array_merge(self::HEADERS_JSON, $additionalHeaders);
        $content = $data !== null ? json_encode($data, JSON_THROW_ON_ERROR) : '';

        return self::createHttpContext($method, $headers, $content, $timeout);
    }

    /**
     * Create HTTP context for form data requests.
     *
     * @param string $method HTTP method
     * @param array<string, mixed> $data Form data
     * @param string[] $additionalHeaders Additional headers
     * @param float $timeout Request timeout in seconds
     * @return array<string, mixed> HTTP context options
     */
    public static function createFormContext(
        string $method,
        array $data = [],
        array $additionalHeaders = [],
        float $timeout = self::DEFAULT_TIMEOUT
    ): array {
        $headers = array_merge(self::HEADERS_FORM, $additionalHeaders);
        $content = http_build_query($data);

        return self::createHttpContext($method, $headers, $content, $timeout);
    }

    /**
     * Create HTTP context for Server-Sent Events.
     *
     * @param string[] $additionalHeaders Additional headers
     * @param float $timeout Request timeout in seconds
     * @return array<string, mixed> HTTP context options
     */
    public static function createSseContext(
        array $additionalHeaders = [],
        float $timeout = self::DEFAULT_TIMEOUT
    ): array {
        $headers = array_merge(self::HEADERS_SSE, $additionalHeaders);

        return self::createHttpContext(self::METHOD_GET, $headers, '', $timeout);
    }

    /**
     * Create HTTP context for MCP Streamable HTTP transport.
     *
     * @param string $method HTTP method
     * @param mixed $data Request data
     * @param string[] $additionalHeaders Additional headers
     * @param float $timeout Request timeout in seconds
     * @return array<string, mixed> HTTP context options
     */
    public static function createStreamableHttpContext(
        string $method,
        $data = null,
        array $additionalHeaders = [],
        float $timeout = self::DEFAULT_TIMEOUT
    ): array {
        $headers = array_merge(self::HEADERS_STREAMABLE_HTTP, $additionalHeaders);
        $content = $data !== null ? json_encode($data, JSON_THROW_ON_ERROR) : '';

        return self::createHttpContext($method, $headers, $content, $timeout);
    }

    /**
     * Validate URL format.
     *
     * @param string $url The URL to validate
     * @return bool True if URL is valid, false otherwise
     */
    public static function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Remove query parameters from URL, keeping only the path.
     *
     * Similar to Python SDK's remove_request_params function.
     *
     * @param string $url The URL to process
     * @return string URL with query parameters removed
     */
    public static function removeRequestParams(string $url): string
    {
        $parsedUrl = parse_url($url);

        if ($parsedUrl === false) {
            return $url;
        }

        $scheme = $parsedUrl['scheme'] ?? '';
        $host = $parsedUrl['host'] ?? '';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $path = $parsedUrl['path'] ?? '/';

        return "{$scheme}://{$host}{$port}{$path}";
    }

    /**
     * Build URL with query parameters.
     *
     * @param string $baseUrl Base URL
     * @param array<string, mixed> $params Query parameters
     * @return string Complete URL with query parameters
     */
    public static function buildUrl(string $baseUrl, array $params = []): string
    {
        if (empty($params)) {
            return $baseUrl;
        }

        $queryString = http_build_query($params);
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl . $separator . $queryString;
    }

    /**
     * Parse HTTP response headers from response string.
     *
     * @param string $response Raw HTTP response
     * @return array{headers: array<string, string>, body: string}
     */
    public static function parseHttpResponse(string $response): array
    {
        $parts = explode("\r\n\r\n", $response, 2);
        $headersPart = $parts[0] ?? '';
        $body = $parts[1] ?? '';

        $headers = [];
        $headerLines = explode("\r\n", $headersPart);

        foreach ($headerLines as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[trim($name)] = trim($value);
            }
        }

        return ['headers' => $headers, 'body' => $body];
    }

    /**
     * Get authorization header for Bearer token.
     *
     * @param string $token Bearer token
     * @return string[] Authorization header array
     */
    public static function getBearerAuthHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    /**
     * Get authorization header for Basic authentication.
     *
     * @param string $username Username
     * @param string $password Password
     * @return string[] Authorization header array
     */
    public static function getBasicAuthHeader(string $username, string $password): array
    {
        $credentials = base64_encode("{$username}:{$password}");
        return ['Authorization' => "Basic {$credentials}"];
    }

    /**
     * Check if HTTP status code indicates success.
     *
     * @param int $statusCode HTTP status code
     * @return bool True if status code indicates success (200-299)
     */
    public static function isSuccessStatusCode(int $statusCode): bool
    {
        return $statusCode >= 200 && $statusCode < 300;
    }

    /**
     * Check if HTTP status code indicates client error.
     *
     * @param int $statusCode HTTP status code
     * @return bool True if status code indicates client error (400-499)
     */
    public static function isClientErrorStatusCode(int $statusCode): bool
    {
        return $statusCode >= 400 && $statusCode < 500;
    }

    /**
     * Check if HTTP status code indicates server error.
     *
     * @param int $statusCode HTTP status code
     * @return bool True if status code indicates server error (500-599)
     */
    public static function isServerErrorStatusCode(int $statusCode): bool
    {
        return $statusCode >= 500 && $statusCode < 600;
    }
}
