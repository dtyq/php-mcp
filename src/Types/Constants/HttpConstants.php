<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Constants;

/**
 * HTTP-specific constants for MCP protocol.
 *
 * Contains HTTP headers, content types, and other HTTP-related
 * constants used in MCP HTTP transport.
 */
final class HttpConstants
{
    // HTTP headers
    public const HTTP_HEADER_SESSION_ID = 'Mcp-Session-Id';

    public const HTTP_HEADER_CONTENT_TYPE = 'Content-Type';

    public const HTTP_HEADER_ACCEPT = 'Accept';

    public const HTTP_HEADER_USER_AGENT = 'User-Agent';

    public const HTTP_HEADER_CACHE_CONTROL = 'Cache-Control';

    public const HTTP_HEADER_AUTHORIZATION = 'Authorization';

    public const HTTP_HEADER_LAST_EVENT_ID = 'Last-Event-ID';

    // HTTP content types
    public const HTTP_CONTENT_TYPE_JSON = 'application/json';

    public const HTTP_CONTENT_TYPE_SSE = 'text/event-stream';

    public const HTTP_CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';

    // HTTP accept headers
    public const HTTP_ACCEPT_SSE_JSON = 'text/event-stream, application/json';

    public const HTTP_ACCEPT_SSE = 'text/event-stream';

    public const HTTP_ACCEPT_JSON = 'application/json';

    // HTTP methods
    public const HTTP_METHOD_GET = 'GET';

    public const HTTP_METHOD_POST = 'POST';

    public const HTTP_METHOD_PUT = 'PUT';

    public const HTTP_METHOD_DELETE = 'DELETE';

    public const HTTP_METHOD_OPTIONS = 'OPTIONS';

    public const HTTP_METHOD_HEAD = 'HEAD';

    // HTTP status codes (commonly used in MCP)
    public const HTTP_STATUS_OK = 200;

    public const HTTP_STATUS_CREATED = 201;

    public const HTTP_STATUS_NO_CONTENT = 204;

    public const HTTP_STATUS_BAD_REQUEST = 400;

    public const HTTP_STATUS_UNAUTHORIZED = 401;

    public const HTTP_STATUS_FORBIDDEN = 403;

    public const HTTP_STATUS_NOT_FOUND = 404;

    public const HTTP_STATUS_METHOD_NOT_ALLOWED = 405;

    public const HTTP_STATUS_CONFLICT = 409;

    public const HTTP_STATUS_INTERNAL_SERVER_ERROR = 500;

    public const HTTP_STATUS_NOT_IMPLEMENTED = 501;

    public const HTTP_STATUS_BAD_GATEWAY = 502;

    public const HTTP_STATUS_SERVICE_UNAVAILABLE = 503;

    /**
     * Get all MCP-specific HTTP headers.
     *
     * @return array<string>
     */
    public static function getMcpHeaders(): array
    {
        return [
            self::HTTP_HEADER_SESSION_ID,
            self::HTTP_HEADER_LAST_EVENT_ID,
        ];
    }

    /**
     * Get all standard HTTP headers used by MCP.
     *
     * @return array<string>
     */
    public static function getStandardHeaders(): array
    {
        return [
            self::HTTP_HEADER_CONTENT_TYPE,
            self::HTTP_HEADER_ACCEPT,
            self::HTTP_HEADER_USER_AGENT,
            self::HTTP_HEADER_CACHE_CONTROL,
            self::HTTP_HEADER_AUTHORIZATION,
        ];
    }

    /**
     * Get all supported HTTP methods.
     *
     * @return array<string>
     */
    public static function getSupportedMethods(): array
    {
        return [
            self::HTTP_METHOD_GET,
            self::HTTP_METHOD_POST,
            self::HTTP_METHOD_PUT,
            self::HTTP_METHOD_DELETE,
            self::HTTP_METHOD_OPTIONS,
            self::HTTP_METHOD_HEAD,
        ];
    }

    /**
     * Check if an HTTP status code indicates success.
     */
    public static function isSuccessStatus(int $statusCode): bool
    {
        return $statusCode >= 200 && $statusCode < 300;
    }

    /**
     * Check if an HTTP status code indicates a client error.
     */
    public static function isClientError(int $statusCode): bool
    {
        return $statusCode >= 400 && $statusCode < 500;
    }

    /**
     * Check if an HTTP status code indicates a server error.
     */
    public static function isServerError(int $statusCode): bool
    {
        return $statusCode >= 500 && $statusCode < 600;
    }

    /**
     * Get the reason phrase for an HTTP status code.
     */
    public static function getReasonPhrase(int $statusCode): string
    {
        $reasonMap = self::getReasonPhraseMap();
        return $reasonMap[$statusCode] ?? 'Unknown';
    }

    /**
     * Check if an HTTP method is safe (read-only).
     */
    public static function isSafeMethod(string $method): bool
    {
        return in_array($method, [
            self::HTTP_METHOD_GET,
            self::HTTP_METHOD_HEAD,
            self::HTTP_METHOD_OPTIONS,
        ], true);
    }

    /**
     * Check if an HTTP method is idempotent.
     */
    public static function isIdempotentMethod(string $method): bool
    {
        return in_array($method, [
            self::HTTP_METHOD_GET,
            self::HTTP_METHOD_HEAD,
            self::HTTP_METHOD_PUT,
            self::HTTP_METHOD_DELETE,
            self::HTTP_METHOD_OPTIONS,
        ], true);
    }

    /**
     * Get HTTP status code to reason phrase mapping.
     *
     * @return array<int, string>
     */
    private static function getReasonPhraseMap(): array
    {
        return [
            self::HTTP_STATUS_OK => 'OK',
            self::HTTP_STATUS_CREATED => 'Created',
            self::HTTP_STATUS_NO_CONTENT => 'No Content',
            self::HTTP_STATUS_BAD_REQUEST => 'Bad Request',
            self::HTTP_STATUS_UNAUTHORIZED => 'Unauthorized',
            self::HTTP_STATUS_FORBIDDEN => 'Forbidden',
            self::HTTP_STATUS_NOT_FOUND => 'Not Found',
            self::HTTP_STATUS_METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::HTTP_STATUS_CONFLICT => 'Conflict',
            self::HTTP_STATUS_INTERNAL_SERVER_ERROR => 'Internal Server Error',
            self::HTTP_STATUS_NOT_IMPLEMENTED => 'Not Implemented',
            self::HTTP_STATUS_BAD_GATEWAY => 'Bad Gateway',
            self::HTTP_STATUS_SERVICE_UNAVAILABLE => 'Service Unavailable',
        ];
    }
}
