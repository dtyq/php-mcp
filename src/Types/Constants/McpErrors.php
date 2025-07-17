<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Constants;

/**
 * MCP-specific error code constants.
 *
 * Contains error codes specific to the Model Context Protocol,
 * extending the JSON-RPC error code range starting from -32000.
 */
final class McpErrors
{
    // MCP-specific error codes (starting from -32000)
    public const MCP_ERROR = -32000;

    public const TRANSPORT_ERROR = -32001;

    public const RESOURCE_NOT_FOUND = -32002;

    public const AUTHENTICATION_ERROR = -32003;

    public const AUTHORIZATION_ERROR = -32004;

    public const VALIDATION_ERROR = -32005;

    public const TOOL_NOT_FOUND = -32006;

    public const PROMPT_NOT_FOUND = -32007;

    public const PROTOCOL_ERROR = -32008;

    public const CAPABILITY_NOT_SUPPORTED = -32009;

    // HTTP error codes (in addition to JSON-RPC codes)
    public const HTTP_ERROR_CONNECTION_FAILED = -32100;

    public const HTTP_ERROR_SESSION_EXPIRED = -32101;

    public const HTTP_ERROR_SSE_CONNECTION_LOST = -32102;

    public const HTTP_ERROR_INVALID_SESSION = -32103;

    /**
     * Get all MCP-specific error codes.
     *
     * @return array<int>
     */
    public static function getMcpErrorCodes(): array
    {
        return [
            self::MCP_ERROR,
            self::TRANSPORT_ERROR,
            self::RESOURCE_NOT_FOUND,
            self::AUTHENTICATION_ERROR,
            self::AUTHORIZATION_ERROR,
            self::VALIDATION_ERROR,
            self::TOOL_NOT_FOUND,
            self::PROMPT_NOT_FOUND,
            self::PROTOCOL_ERROR,
            self::CAPABILITY_NOT_SUPPORTED,
        ];
    }

    /**
     * Get all HTTP-specific error codes.
     *
     * @return array<int>
     */
    public static function getHttpErrorCodes(): array
    {
        return [
            self::HTTP_ERROR_CONNECTION_FAILED,
            self::HTTP_ERROR_SESSION_EXPIRED,
            self::HTTP_ERROR_SSE_CONNECTION_LOST,
            self::HTTP_ERROR_INVALID_SESSION,
        ];
    }

    /**
     * Check if an error code is an MCP error.
     */
    public static function isMcpError(int $code): bool
    {
        return in_array($code, self::getMcpErrorCodes(), true);
    }

    /**
     * Check if an error code is an HTTP-related error.
     */
    public static function isHttpError(int $code): bool
    {
        return in_array($code, self::getHttpErrorCodes(), true);
    }

    /**
     * Get error message for an MCP error code.
     */
    public static function getErrorMessage(int $code): ?string
    {
        $messageMap = self::getErrorMessageMap();
        return $messageMap[$code] ?? null;
    }

    /**
     * Get error code to message mapping.
     *
     * @return array<int, string>
     */
    private static function getErrorMessageMap(): array
    {
        return [
            self::MCP_ERROR => 'MCP error',
            self::TRANSPORT_ERROR => 'Transport error',
            self::RESOURCE_NOT_FOUND => 'Resource not found',
            self::AUTHENTICATION_ERROR => 'Authentication error',
            self::AUTHORIZATION_ERROR => 'Authorization error',
            self::VALIDATION_ERROR => 'Validation error',
            self::TOOL_NOT_FOUND => 'Tool not found',
            self::PROMPT_NOT_FOUND => 'Prompt not found',
            self::PROTOCOL_ERROR => 'Protocol error',
            self::CAPABILITY_NOT_SUPPORTED => 'Capability not supported',
            self::HTTP_ERROR_CONNECTION_FAILED => 'HTTP connection failed',
            self::HTTP_ERROR_SESSION_EXPIRED => 'HTTP session expired',
            self::HTTP_ERROR_SSE_CONNECTION_LOST => 'SSE connection lost',
            self::HTTP_ERROR_INVALID_SESSION => 'Invalid HTTP session',
        ];
    }
}
