<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Message;

use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use stdClass;

/**
 * Utilities for working with MCP messages and JSON-RPC messages.
 *
 * This class provides helper methods for creating, validating, and processing
 * various types of MCP protocol messages.
 */
class MessageUtils
{
    /**
     * MCP protocol version.
     */
    public const MCP_PROTOCOL_VERSION = '2024-11-05';

    /**
     * Common MCP methods.
     */
    public const METHOD_INITIALIZE = 'initialize';

    public const METHOD_INITIALIZED = 'notifications/initialized';

    public const METHOD_PING = 'ping';

    public const METHOD_LIST_TOOLS = 'tools/list';

    public const METHOD_CALL_TOOL = 'tools/call';

    public const METHOD_LIST_RESOURCES = 'resources/list';

    public const METHOD_READ_RESOURCE = 'resources/read';

    public const METHOD_LIST_PROMPTS = 'prompts/list';

    public const METHOD_GET_PROMPT = 'prompts/get';

    public const METHOD_CREATE_MESSAGE = 'sampling/createMessage';

    public const METHOD_LIST_ROOTS = 'roots/list';

    /**
     * Notification methods.
     */
    public const NOTIFICATION_PROGRESS = 'notifications/progress';

    public const NOTIFICATION_LOGGING = 'notifications/message';

    public const NOTIFICATION_RESOURCE_UPDATED = 'notifications/resources/updated';

    public const NOTIFICATION_RESOURCE_LIST_CHANGED = 'notifications/resources/list_changed';

    public const NOTIFICATION_TOOL_LIST_CHANGED = 'notifications/tools/list_changed';

    public const NOTIFICATION_PROMPT_LIST_CHANGED = 'notifications/prompts/list_changed';

    /**
     * Create an MCP initialize request.
     *
     * @param int|string $requestId Request ID
     * @param array{name: string, version: string} $clientInfo Client implementation info
     * @param array<string, mixed> $capabilities Client capabilities
     */
    public static function createInitializeRequest(
        $requestId,
        array $clientInfo,
        array $capabilities = []
    ): JsonRpcMessage {
        $params = [
            'protocolVersion' => self::MCP_PROTOCOL_VERSION,
            'clientInfo' => $clientInfo,
            'capabilities' => $capabilities,
        ];

        return JsonRpcMessage::createRequest(self::METHOD_INITIALIZE, $params, $requestId);
    }

    /**
     * Create an MCP initialize response.
     *
     * @param int|string $requestId Request ID being responded to
     * @param array{name: string, version: string} $serverInfo Server implementation info
     * @param array<string, mixed> $capabilities Server capabilities
     */
    public static function createInitializeResponse(
        $requestId,
        array $serverInfo,
        array $capabilities = []
    ): JsonRpcMessage {
        $result = [
            'protocolVersion' => self::MCP_PROTOCOL_VERSION,
            'serverInfo' => $serverInfo,
            'capabilities' => $capabilities,
        ];

        return JsonRpcMessage::createResponse($requestId, $result);
    }

    /**
     * Create an initialized notification.
     */
    public static function createInitializedNotification(): JsonRpcMessage
    {
        return JsonRpcMessage::createNotification(self::METHOD_INITIALIZED);
    }

    /**
     * Create a ping request.
     *
     * @param int|string $requestId Request ID
     */
    public static function createPingRequest($requestId): JsonRpcMessage
    {
        return JsonRpcMessage::createRequest(self::METHOD_PING, null, $requestId);
    }

    /**
     * Create a pong response.
     *
     * @param int|string $requestId Request ID being responded to
     */
    public static function createPongResponse($requestId): JsonRpcMessage
    {
        return JsonRpcMessage::createResponse($requestId, new stdClass());
    }

    /**
     * Create a tools/list request.
     *
     * @param int|string $requestId Request ID
     * @param null|string $cursor Optional cursor for pagination
     */
    public static function createListToolsRequest($requestId, ?string $cursor = null): JsonRpcMessage
    {
        $params = [];
        if ($cursor !== null) {
            $params['cursor'] = $cursor;
        }

        return JsonRpcMessage::createRequest(
            self::METHOD_LIST_TOOLS,
            empty($params) ? null : $params,
            $requestId
        );
    }

    /**
     * Create a tools/call request.
     *
     * @param int|string $requestId Request ID
     * @param string $name Tool name
     * @param array<string, mixed> $arguments Tool arguments
     */
    public static function createCallToolRequest($requestId, string $name, array $arguments = []): JsonRpcMessage
    {
        $params = [
            'name' => $name,
            'arguments' => $arguments,
        ];

        return JsonRpcMessage::createRequest(self::METHOD_CALL_TOOL, $params, $requestId);
    }

    /**
     * Create a resources/list request.
     *
     * @param int|string $requestId Request ID
     * @param null|string $cursor Optional cursor for pagination
     */
    public static function createListResourcesRequest($requestId, ?string $cursor = null): JsonRpcMessage
    {
        $params = [];
        if ($cursor !== null) {
            $params['cursor'] = $cursor;
        }

        return JsonRpcMessage::createRequest(
            self::METHOD_LIST_RESOURCES,
            empty($params) ? null : $params,
            $requestId
        );
    }

    /**
     * Create a resources/read request.
     *
     * @param int|string $requestId Request ID
     * @param string $uri Resource URI
     */
    public static function createReadResourceRequest($requestId, string $uri): JsonRpcMessage
    {
        $params = ['uri' => $uri];

        return JsonRpcMessage::createRequest(self::METHOD_READ_RESOURCE, $params, $requestId);
    }

    /**
     * Create a progress notification.
     *
     * @param int|string $progressToken Progress token
     * @param float $progress Current progress (0.0 to 1.0)
     * @param null|float $total Total work units (optional)
     */
    public static function createProgressNotification($progressToken, float $progress, ?float $total = null): JsonRpcMessage
    {
        $params = [
            'progressToken' => $progressToken,
            'progress' => $progress,
        ];

        if ($total !== null) {
            $params['total'] = $total;
        }

        return JsonRpcMessage::createNotification(self::NOTIFICATION_PROGRESS, $params);
    }

    /**
     * Create a logging notification.
     *
     * @param string $level Log level (debug, info, notice, warning, error, critical, alert, emergency)
     * @param string $data Log message or structured data
     * @param null|string $logger Logger name
     */
    public static function createLoggingNotification(string $level, string $data, ?string $logger = null): JsonRpcMessage
    {
        $params = [
            'level' => $level,
            'data' => $data,
        ];

        if ($logger !== null) {
            $params['logger'] = $logger;
        }

        return JsonRpcMessage::createNotification(self::NOTIFICATION_LOGGING, $params);
    }

    /**
     * Create an error response.
     *
     * @param null|int|string $requestId Request ID being responded to (null for parse errors)
     * @param int $code Error code
     * @param string $message Error message
     * @param mixed $data Optional error data
     */
    public static function createErrorResponse($requestId, int $code, string $message, $data = null): JsonRpcMessage
    {
        $error = ['code' => $code, 'message' => $message];
        if ($data !== null) {
            $error['data'] = $data;
        }

        return JsonRpcMessage::createError($requestId, $error);
    }

    /**
     * Create a JSON-RPC parse error response.
     *
     * @param string $message Optional custom message
     */
    public static function createParseErrorResponse(string $message = 'Parse error'): JsonRpcMessage
    {
        return self::createErrorResponse(null, ErrorCodes::PARSE_ERROR, $message);
    }

    /**
     * Create a JSON-RPC invalid request error response.
     *
     * @param null|int|string $requestId Request ID if available
     * @param string $message Optional custom message
     */
    public static function createInvalidRequestErrorResponse($requestId = null, string $message = 'Invalid Request'): JsonRpcMessage
    {
        return self::createErrorResponse($requestId, ErrorCodes::INVALID_REQUEST, $message);
    }

    /**
     * Create a method not found error response.
     *
     * @param int|string $requestId Request ID
     * @param string $method Method name that was not found
     */
    public static function createMethodNotFoundErrorResponse($requestId, string $method): JsonRpcMessage
    {
        return self::createErrorResponse(
            $requestId,
            ErrorCodes::METHOD_NOT_FOUND,
            "Method not found: {$method}"
        );
    }

    /**
     * Create an invalid params error response.
     *
     * @param int|string $requestId Request ID
     * @param string $message Optional custom message
     */
    public static function createInvalidParamsErrorResponse($requestId, string $message = 'Invalid params'): JsonRpcMessage
    {
        return self::createErrorResponse($requestId, ErrorCodes::INVALID_PARAMS, $message);
    }

    /**
     * Validate a JSON-RPC message.
     *
     * @param JsonRpcMessage $message Message to validate
     * @return bool True if valid
     * @throws ValidationError If message is invalid
     */
    public static function validateMessage(JsonRpcMessage $message): bool
    {
        if (! $message->isValid()) {
            throw ValidationError::invalidJsonFormat('Invalid JSON-RPC message structure');
        }

        return true;
    }

    /**
     * Extract request ID from message, handling null IDs for notifications.
     *
     * @param JsonRpcMessage $message JSON-RPC message
     * @return null|int|string Request ID or null for notifications
     */
    public static function extractRequestId(JsonRpcMessage $message)
    {
        return $message->getId();
    }

    /**
     * Check if a method is a MCP standard method.
     *
     * @param string $method Method name
     * @return bool True if method is a standard MCP method
     */
    public static function isStandardMethod(string $method): bool
    {
        $standardMethods = [
            self::METHOD_INITIALIZE,
            self::METHOD_INITIALIZED,
            self::METHOD_PING,
            self::METHOD_LIST_TOOLS,
            self::METHOD_CALL_TOOL,
            self::METHOD_LIST_RESOURCES,
            self::METHOD_READ_RESOURCE,
            self::METHOD_LIST_PROMPTS,
            self::METHOD_GET_PROMPT,
            self::METHOD_CREATE_MESSAGE,
            self::METHOD_LIST_ROOTS,
        ];

        return in_array($method, $standardMethods, true);
    }

    /**
     * Check if a method is a notification method.
     *
     * @param string $method Method name
     * @return bool True if method is a notification
     */
    public static function isNotificationMethod(string $method): bool
    {
        return str_starts_with($method, 'notifications/');
    }

    /**
     * Generate a unique request ID.
     *
     * @return string Unique request ID
     */
    public static function generateRequestId(): string
    {
        return uniqid('req_', true);
    }

    /**
     * Create a session message with optional metadata.
     *
     * @param JsonRpcMessage $message JSON-RPC message
     * @param mixed $metadata Optional metadata
     */
    public static function createSessionMessage(JsonRpcMessage $message, $metadata = null): SessionMessage
    {
        return new SessionMessage($message, $metadata);
    }
}
