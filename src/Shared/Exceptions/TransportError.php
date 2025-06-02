<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Exceptions;

/**
 * Exception for transport layer errors.
 *
 * This exception is thrown when there are errors in the transport layer,
 * such as connection issues, network problems, or transport-specific errors.
 */
class TransportError extends McpError
{
    /**
     * Create a TransportError with a specific error message.
     *
     * @param string $message The error message
     * @param mixed $data Additional error data (optional)
     */
    public function __construct(string $message, $data = null)
    {
        $error = new ErrorData(ErrorCodes::TRANSPORT_ERROR, $message, $data);
        parent::__construct($error);
    }

    /**
     * Create a TransportError for connection lost.
     *
     * @param string $transport The transport type
     * @param mixed $data Additional error data (optional)
     */
    public static function connectionLost(string $transport, $data = null): TransportError
    {
        $error = new ErrorData(ErrorCodes::CONNECTION_LOST, "Connection lost on {$transport} transport", $data);
        $exception = new McpError($error);
        return new self($exception->getMessage(), $data);
    }

    /**
     * Create a TransportError for connection timeout.
     *
     * @param string $transport The transport type
     * @param int $timeoutSeconds The timeout duration in seconds
     * @param mixed $data Additional error data (optional)
     */
    public static function connectionTimeout(string $transport, int $timeoutSeconds, $data = null): TransportError
    {
        return new self("Connection timeout after {$timeoutSeconds}s on {$transport} transport", $data);
    }

    /**
     * Create a TransportError for connection refused.
     *
     * @param string $transport The transport type
     * @param string $endpoint The endpoint that refused connection
     * @param mixed $data Additional error data (optional)
     */
    public static function connectionRefused(string $transport, string $endpoint, $data = null): TransportError
    {
        return new self("Connection refused to {$endpoint} on {$transport} transport", $data);
    }

    /**
     * Create a TransportError for unsupported transport.
     *
     * @param string $transport The unsupported transport type
     * @param string[] $supportedTransports List of supported transports
     * @param mixed $data Additional error data (optional)
     */
    public static function unsupportedTransport(string $transport, array $supportedTransports = [], $data = null): TransportError
    {
        $supported = empty($supportedTransports) ? '' : ' (supported: ' . implode(', ', $supportedTransports) . ')';
        return new self("Unsupported transport: {$transport}{$supported}", $data);
    }

    /**
     * Create a TransportError for HTTP-specific errors.
     *
     * @param int $httpCode The HTTP status code
     * @param string $reason The reason phrase
     * @param mixed $data Additional error data (optional)
     */
    public static function httpError(int $httpCode, string $reason, $data = null): TransportError
    {
        switch ($httpCode) {
            case 400:
                $errorCode = ErrorCodes::HTTP_BAD_REQUEST;
                break;
            case 401:
                $errorCode = ErrorCodes::HTTP_UNAUTHORIZED;
                break;
            case 403:
                $errorCode = ErrorCodes::HTTP_FORBIDDEN;
                break;
            case 404:
                $errorCode = ErrorCodes::HTTP_NOT_FOUND;
                break;
            case 405:
                $errorCode = ErrorCodes::HTTP_METHOD_NOT_ALLOWED;
                break;
            case 408:
                $errorCode = ErrorCodes::HTTP_REQUEST_TIMEOUT;
                break;
            case 500:
                $errorCode = ErrorCodes::HTTP_INTERNAL_SERVER_ERROR;
                break;
            case 502:
                $errorCode = ErrorCodes::HTTP_BAD_GATEWAY;
                break;
            case 503:
                $errorCode = ErrorCodes::HTTP_SERVICE_UNAVAILABLE;
                break;
            case 504:
                $errorCode = ErrorCodes::HTTP_GATEWAY_TIMEOUT;
                break;
            default:
                $errorCode = ErrorCodes::TRANSPORT_ERROR;
                break;
        }

        $error = new ErrorData($errorCode, "HTTP {$httpCode}: {$reason}", $data);
        $exception = new McpError($error);
        return new self($exception->getMessage(), $data);
    }

    /**
     * Create a TransportError for StreamableHTTP-specific errors.
     *
     * @param string $type The type of StreamableHTTP error
     * @param string $message The error message
     * @param mixed $data Additional error data (optional)
     */
    public static function streamableHttpError(string $type, string $message, $data = null): TransportError
    {
        switch ($type) {
            case 'session_not_found':
                $errorCode = ErrorCodes::STREAMABLE_HTTP_SESSION_NOT_FOUND;
                break;
            case 'session_expired':
                $errorCode = ErrorCodes::STREAMABLE_HTTP_SESSION_EXPIRED;
                break;
            case 'resumption_error':
                $errorCode = ErrorCodes::STREAMABLE_HTTP_RESUMPTION_ERROR;
                break;
            case 'invalid_session':
                $errorCode = ErrorCodes::STREAMABLE_HTTP_INVALID_SESSION;
                break;
            default:
                $errorCode = ErrorCodes::TRANSPORT_ERROR;
                break;
        }

        $error = new ErrorData($errorCode, "StreamableHTTP error: {$message}", $data);
        $exception = new McpError($error);
        return new self($exception->getMessage(), $data);
    }

    /**
     * Create a TransportError for STDIO transport errors.
     *
     * @param string $message The error message
     * @param mixed $data Additional error data (optional)
     */
    public static function stdioError(string $message, $data = null): TransportError
    {
        return new self("STDIO transport error: {$message}", $data);
    }

    /**
     * Create a TransportError for SSE transport errors.
     *
     * @param string $message The error message
     * @param mixed $data Additional error data (optional)
     */
    public static function sseError(string $message, $data = null): TransportError
    {
        return new self("SSE transport error: {$message}", $data);
    }

    /**
     * Create a TransportError for WebSocket transport errors.
     *
     * @param string $message The error message
     * @param mixed $data Additional error data (optional)
     */
    public static function webSocketError(string $message, $data = null): TransportError
    {
        return new self("WebSocket transport error: {$message}", $data);
    }

    /**
     * Create a TransportError for malformed message.
     *
     * @param string $transport The transport type
     * @param string $reason The reason for malformed message
     * @param mixed $data Additional error data (optional)
     */
    public static function malformedMessage(string $transport, string $reason, $data = null): TransportError
    {
        return new self("Malformed message on {$transport} transport: {$reason}", $data);
    }

    /**
     * Create a TransportError for encoding/decoding errors.
     *
     * @param string $operation The operation (encode/decode)
     * @param string $reason The reason for failure
     * @param mixed $data Additional error data (optional)
     */
    public static function encodingError(string $operation, string $reason, $data = null): TransportError
    {
        return new self("Message {$operation} error: {$reason}", $data);
    }

    /**
     * Create a TransportError for transport not initialized.
     *
     * @param string $transport The transport type
     * @param mixed $data Additional error data (optional)
     */
    public static function notInitialized(string $transport, $data = null): TransportError
    {
        return new self("Transport {$transport} is not initialized", $data);
    }

    /**
     * Create a TransportError for transport already started.
     *
     * @param string $transport The transport type
     * @param mixed $data Additional error data (optional)
     */
    public static function alreadyStarted(string $transport, $data = null): TransportError
    {
        return new self("Transport {$transport} is already started", $data);
    }

    /**
     * Create a TransportError for transport not started.
     *
     * @param string $transport The transport type
     * @param mixed $data Additional error data (optional)
     */
    public static function notStarted(string $transport, $data = null): TransportError
    {
        return new self("Transport {$transport} is not started", $data);
    }

    /**
     * Create a TransportError for configuration errors.
     *
     * @param string $message The configuration error message
     * @param mixed $data Additional error data (optional)
     */
    public static function configurationError(string $message, $data = null): TransportError
    {
        return new self("Configuration error: {$message}", $data);
    }

    /**
     * Create a TransportError for startup failures.
     *
     * @param string $transport The transport type
     * @param string $reason The reason for startup failure
     * @param mixed $data Additional error data (optional)
     */
    public static function startupFailed(string $transport, string $reason, $data = null): TransportError
    {
        return new self("Failed to start {$transport} transport: {$reason}", $data);
    }

    /**
     * Create a TransportError for shutdown failures.
     *
     * @param string $transport The transport type
     * @param string $reason The reason for shutdown failure
     * @param mixed $data Additional error data (optional)
     */
    public static function shutdownFailed(string $transport, string $reason, $data = null): TransportError
    {
        return new self("Failed to shutdown {$transport} transport: {$reason}", $data);
    }

    /**
     * Create a TransportError for shutdown in progress.
     *
     * @param string $transport The transport type
     * @param mixed $data Additional error data (optional)
     */
    public static function shutdownInProgress(string $transport, $data = null): TransportError
    {
        return new self("Transport {$transport} is shutting down", $data);
    }

    /**
     * Create a TransportError for message send failures.
     *
     * @param string $transport The transport type
     * @param string $reason The reason for send failure
     * @param mixed $data Additional error data (optional)
     */
    public static function messageSendFailed(string $transport, string $reason, $data = null): TransportError
    {
        return new self("Failed to send message on {$transport} transport: {$reason}", $data);
    }
}
