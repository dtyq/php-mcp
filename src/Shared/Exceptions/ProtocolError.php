<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Exceptions;

/**
 * Exception for MCP protocol-specific errors.
 *
 * This exception is thrown when there are violations of the MCP protocol
 * specification, such as invalid message format, unsupported methods,
 * or protocol state violations.
 */
class ProtocolError extends McpError
{
    /**
     * Create a ProtocolError with a specific error message.
     *
     * @param string $message The error message
     * @param mixed $data Additional error data (optional)
     */
    public function __construct(string $message, $data = null)
    {
        $error = new ErrorData(ErrorCodes::PROTOCOL_ERROR, $message, $data);
        parent::__construct($error);
    }

    /**
     * Create a ProtocolError for invalid method.
     *
     * @param string $method The invalid method name
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidMethod(string $method, $data = null): ProtocolError
    {
        return new self("Invalid or unsupported method: {$method}", $data);
    }

    /**
     * Create a ProtocolError for invalid parameters.
     *
     * @param string $method The method name
     * @param string $reason The reason for invalid parameters
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidParams(string $method, string $reason, $data = null): ProtocolError
    {
        return new self("Invalid parameters for method '{$method}': {$reason}", $data);
    }

    /**
     * Create a ProtocolError for protocol version mismatch.
     *
     * @param string $clientVersion The client's protocol version
     * @param string $serverVersion The server's protocol version
     * @param mixed $data Additional error data (optional)
     */
    public static function versionMismatch(string $clientVersion, string $serverVersion, $data = null): ProtocolError
    {
        return new self(
            "Protocol version mismatch: client={$clientVersion}, server={$serverVersion}",
            $data
        );
    }

    /**
     * Create a ProtocolError for invalid message format.
     *
     * @param string $reason The reason for invalid format
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidFormat(string $reason, $data = null): ProtocolError
    {
        return new self("Invalid message format: {$reason}", $data);
    }

    /**
     * Create a ProtocolError for capability not supported.
     *
     * @param string $capability The unsupported capability
     * @param mixed $data Additional error data (optional)
     */
    public static function capabilityNotSupported(string $capability, $data = null): ProtocolError
    {
        $error = new ErrorData(ErrorCodes::CAPABILITY_NOT_SUPPORTED, "Capability not supported: {$capability}", $data);
        $exception = new McpError($error);
        return new self($exception->getMessage(), $data);
    }

    /**
     * Create a ProtocolError for invalid state.
     *
     * @param string $operation The operation that failed
     * @param string $currentState The current state
     * @param string $expectedState The expected state
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidState(
        string $operation,
        string $currentState,
        string $expectedState,
        $data = null
    ): ProtocolError {
        return new self(
            "Invalid state for operation '{$operation}': current={$currentState}, expected={$expectedState}",
            $data
        );
    }

    /**
     * Create a ProtocolError for missing required fields.
     *
     * @param string[] $missingFields Array of missing field names
     * @param mixed $data Additional error data (optional)
     */
    public static function missingRequiredFields(array $missingFields, $data = null): ProtocolError
    {
        $fields = implode(', ', $missingFields);
        return new self("Missing required fields: {$fields}", $data);
    }

    /**
     * Create a ProtocolError for resource not found.
     *
     * @param string $uri The resource URI that was not found
     * @param mixed $data Additional error data (optional)
     */
    public static function resourceNotFound(string $uri, $data = null): ProtocolError
    {
        $error = new ErrorData(ErrorCodes::RESOURCE_NOT_FOUND, "Resource not found: {$uri}", $data);
        $exception = new McpError($error);
        return new self($exception->getMessage(), $data);
    }

    /**
     * Create a ProtocolError for tool not found.
     *
     * @param string $toolName The tool name that was not found
     * @param mixed $data Additional error data (optional)
     */
    public static function toolNotFound(string $toolName, $data = null): ProtocolError
    {
        $error = new ErrorData(ErrorCodes::TOOL_NOT_FOUND, "Tool not found: {$toolName}", $data);
        $exception = new McpError($error);
        return new self($exception->getMessage(), $data);
    }

    /**
     * Create a ProtocolError for prompt not found.
     *
     * @param string $promptName The prompt name that was not found
     * @param mixed $data Additional error data (optional)
     */
    public static function promptNotFound(string $promptName, $data = null): ProtocolError
    {
        $error = new ErrorData(ErrorCodes::PROMPT_NOT_FOUND, "Prompt not found: {$promptName}", $data);
        $exception = new McpError($error);
        return new self($exception->getMessage(), $data);
    }
}
