<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Exceptions;

/**
 * Error codes for JSON-RPC 2.0 and MCP protocol.
 *
 * Based on JSON-RPC 2.0 specification and MCP protocol extensions.
 * These constants correspond to the error codes defined in Python SDK.
 */
final class ErrorCodes
{
    // Standard JSON-RPC 2.0 error codes (from Python SDK types.py)
    public const PARSE_ERROR = -32700;

    public const INVALID_REQUEST = -32600;

    public const METHOD_NOT_FOUND = -32601;

    public const INVALID_PARAMS = -32602;

    public const INTERNAL_ERROR = -32603;

    // JSON-RPC 2.0 reserved error codes range: -32768 to -32000
    // Server errors are reserved for the range -32099 to -32000

    // MCP-specific error codes (application-level errors)
    public const MCP_ERROR = -32000;

    public const TRANSPORT_ERROR = -32001;

    public const PROTOCOL_ERROR = -32002;

    public const AUTHENTICATION_ERROR = -32003;

    public const AUTHORIZATION_ERROR = -32004;

    public const VALIDATION_ERROR = -32005;

    public const RESOURCE_NOT_FOUND = -32006;

    public const TOOL_NOT_FOUND = -32007;

    public const PROMPT_NOT_FOUND = -32008;

    public const CAPABILITY_NOT_SUPPORTED = -32009;

    public const SESSION_ERROR = -32010;

    public const TIMEOUT_ERROR = -32011;

    public const RATE_LIMIT_ERROR = -32012;

    public const QUOTA_EXCEEDED_ERROR = -32013;

    public const RESOURCE_UNAVAILABLE = -32014;

    public const OPERATION_CANCELLED = -32015;

    // OAuth/Authentication specific errors
    public const OAUTH_INVALID_SCOPE = -32020;

    public const OAUTH_INVALID_REDIRECT_URI = -32021;

    public const OAUTH_INVALID_CLIENT = -32022;

    public const OAUTH_INVALID_GRANT = -32023;

    public const OAUTH_UNAUTHORIZED_CLIENT = -32024;

    public const OAUTH_UNSUPPORTED_GRANT_TYPE = -32025;

    public const OAUTH_INVALID_REQUEST = -32026;

    public const OAUTH_ACCESS_DENIED = -32027;

    public const OAUTH_UNSUPPORTED_RESPONSE_TYPE = -32028;

    public const OAUTH_SERVER_ERROR = -32029;

    public const OAUTH_TEMPORARILY_UNAVAILABLE = -32030;

    // HTTP transport specific errors
    public const HTTP_BAD_REQUEST = -32040;

    public const HTTP_UNAUTHORIZED = -32041;

    public const HTTP_FORBIDDEN = -32042;

    public const HTTP_NOT_FOUND = -32043;

    public const HTTP_METHOD_NOT_ALLOWED = -32044;

    public const HTTP_REQUEST_TIMEOUT = -32045;

    public const HTTP_INTERNAL_SERVER_ERROR = -32046;

    public const HTTP_BAD_GATEWAY = -32047;

    public const HTTP_SERVICE_UNAVAILABLE = -32048;

    public const HTTP_GATEWAY_TIMEOUT = -32049;

    // StreamableHTTP specific errors
    public const STREAMABLE_HTTP_SESSION_NOT_FOUND = -32050;

    public const STREAMABLE_HTTP_SESSION_EXPIRED = -32051;

    public const STREAMABLE_HTTP_RESUMPTION_ERROR = -32052;

    public const STREAMABLE_HTTP_INVALID_SESSION = -32053;

    // Connection and transport errors
    public const CONNECTION_LOST = -32060;

    public const CONNECTION_TIMEOUT = -32061;

    public const CONNECTION_REFUSED = -32062;

    public const PROTOCOL_VERSION_MISMATCH = -32063;

    public const UNSUPPORTED_TRANSPORT = -32064;

    /**
     * Get human-readable error message for a given error code.
     *
     * @param int $code The error code
     * @return string Human-readable error message
     */
    public static function getErrorMessage(int $code): string
    {
        switch ($code) {
            // Standard JSON-RPC errors
            case self::PARSE_ERROR:
                return 'Parse error: Invalid JSON was received by the server';
            case self::INVALID_REQUEST:
                return 'Invalid Request: The JSON sent is not a valid Request object';
            case self::METHOD_NOT_FOUND:
                return 'Method not found: The method does not exist / is not available';
            case self::INVALID_PARAMS:
                return 'Invalid params: Invalid method parameter(s)';
            case self::INTERNAL_ERROR:
                return 'Internal error: Internal JSON-RPC error';
                // MCP-specific errors
            case self::MCP_ERROR:
                return 'MCP error: General MCP protocol error';
            case self::TRANSPORT_ERROR:
                return 'Transport error: Error in the transport layer';
            case self::PROTOCOL_ERROR:
                return 'Protocol error: MCP protocol violation';
            case self::AUTHENTICATION_ERROR:
                return 'Authentication error: Authentication failed';
            case self::AUTHORIZATION_ERROR:
                return 'Authorization error: Insufficient permissions';
            case self::VALIDATION_ERROR:
                return 'Validation error: Input validation failed';
            case self::RESOURCE_NOT_FOUND:
                return 'Resource not found: The requested resource does not exist';
            case self::TOOL_NOT_FOUND:
                return 'Tool not found: The requested tool does not exist';
            case self::PROMPT_NOT_FOUND:
                return 'Prompt not found: The requested prompt does not exist';
            case self::CAPABILITY_NOT_SUPPORTED:
                return 'Capability not supported: The requested capability is not supported';
            case self::SESSION_ERROR:
                return 'Session error: Error in session management';
            case self::TIMEOUT_ERROR:
                return 'Timeout error: Operation timed out';
            case self::RATE_LIMIT_ERROR:
                return 'Rate limit error: Too many requests';
            case self::QUOTA_EXCEEDED_ERROR:
                return 'Quota exceeded: Resource quota exceeded';
            case self::RESOURCE_UNAVAILABLE:
                return 'Resource unavailable: The requested resource is temporarily unavailable';
            case self::OPERATION_CANCELLED:
                return 'Operation cancelled: The operation was cancelled';
                // OAuth errors
            case self::OAUTH_INVALID_SCOPE:
                return 'OAuth error: Invalid scope';
            case self::OAUTH_INVALID_REDIRECT_URI:
                return 'OAuth error: Invalid redirect URI';
            case self::OAUTH_INVALID_CLIENT:
                return 'OAuth error: Invalid client';
            case self::OAUTH_INVALID_GRANT:
                return 'OAuth error: Invalid grant';
            case self::OAUTH_UNAUTHORIZED_CLIENT:
                return 'OAuth error: Unauthorized client';
            case self::OAUTH_UNSUPPORTED_GRANT_TYPE:
                return 'OAuth error: Unsupported grant type';
            case self::OAUTH_INVALID_REQUEST:
                return 'OAuth error: Invalid request';
            case self::OAUTH_ACCESS_DENIED:
                return 'OAuth error: Access denied';
            case self::OAUTH_UNSUPPORTED_RESPONSE_TYPE:
                return 'OAuth error: Unsupported response type';
            case self::OAUTH_SERVER_ERROR:
                return 'OAuth error: Server error';
            case self::OAUTH_TEMPORARILY_UNAVAILABLE:
                return 'OAuth error: Temporarily unavailable';
                // HTTP transport errors
            case self::HTTP_BAD_REQUEST:
                return 'HTTP error: Bad request';
            case self::HTTP_UNAUTHORIZED:
                return 'HTTP error: Unauthorized';
            case self::HTTP_FORBIDDEN:
                return 'HTTP error: Forbidden';
            case self::HTTP_NOT_FOUND:
                return 'HTTP error: Not found';
            case self::HTTP_METHOD_NOT_ALLOWED:
                return 'HTTP error: Method not allowed';
            case self::HTTP_REQUEST_TIMEOUT:
                return 'HTTP error: Request timeout';
            case self::HTTP_INTERNAL_SERVER_ERROR:
                return 'HTTP error: Internal server error';
            case self::HTTP_BAD_GATEWAY:
                return 'HTTP error: Bad gateway';
            case self::HTTP_SERVICE_UNAVAILABLE:
                return 'HTTP error: Service unavailable';
            case self::HTTP_GATEWAY_TIMEOUT:
                return 'HTTP error: Gateway timeout';
                // StreamableHTTP errors
            case self::STREAMABLE_HTTP_SESSION_NOT_FOUND:
                return 'StreamableHTTP error: Session not found';
            case self::STREAMABLE_HTTP_SESSION_EXPIRED:
                return 'StreamableHTTP error: Session expired';
            case self::STREAMABLE_HTTP_RESUMPTION_ERROR:
                return 'StreamableHTTP error: Resumption failed';
            case self::STREAMABLE_HTTP_INVALID_SESSION:
                return 'StreamableHTTP error: Invalid session';
                // Connection errors
            case self::CONNECTION_LOST:
                return 'Connection error: Connection lost';
            case self::CONNECTION_TIMEOUT:
                return 'Connection error: Connection timeout';
            case self::CONNECTION_REFUSED:
                return 'Connection error: Connection refused';
            case self::PROTOCOL_VERSION_MISMATCH:
                return 'Protocol error: Version mismatch';
            case self::UNSUPPORTED_TRANSPORT:
                return 'Transport error: Unsupported transport';
            default:
                return "Unknown error (code: {$code})";
        }
    }

    /**
     * Check if an error code is a standard JSON-RPC error.
     *
     * @param int $code The error code to check
     * @return bool True if it's a standard JSON-RPC error
     */
    public static function isJsonRpcError(int $code): bool
    {
        return in_array($code, [
            self::PARSE_ERROR,
            self::INVALID_REQUEST,
            self::METHOD_NOT_FOUND,
            self::INVALID_PARAMS,
            self::INTERNAL_ERROR,
        ], true);
    }

    /**
     * Check if an error code is within the reserved JSON-RPC range.
     *
     * @param int $code The error code to check
     * @return bool True if it's in the reserved range
     */
    public static function isReservedJsonRpcError(int $code): bool
    {
        return $code >= -32768 && $code <= -32000;
    }

    /**
     * Check if an error code is MCP-specific.
     *
     * @param int $code The error code to check
     * @return bool True if it's an MCP-specific error
     */
    public static function isMcpError(int $code): bool
    {
        return $code >= -32099 && $code <= -32000 && ! self::isJsonRpcError($code);
    }
}
