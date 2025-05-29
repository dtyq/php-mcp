<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Message;

use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
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
            'protocolVersion' => ProtocolConstants::LATEST_PROTOCOL_VERSION,
            'clientInfo' => $clientInfo,
            'capabilities' => $capabilities,
        ];

        return JsonRpcMessage::createRequest(ProtocolConstants::METHOD_INITIALIZE, $params, $requestId);
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
            'protocolVersion' => ProtocolConstants::LATEST_PROTOCOL_VERSION,
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
        return JsonRpcMessage::createNotification(ProtocolConstants::NOTIFICATION_INITIALIZED);
    }

    /**
     * Create a ping request.
     *
     * @param int|string $requestId Request ID
     */
    public static function createPingRequest($requestId): JsonRpcMessage
    {
        return JsonRpcMessage::createRequest(ProtocolConstants::METHOD_PING, null, $requestId);
    }

    /**
     * Create a pong response.
     *
     * @param int|string $requestId Request ID being responded to
     * @return JsonRpcMessage The pong response
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
            ProtocolConstants::METHOD_TOOLS_LIST,
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

        return JsonRpcMessage::createRequest(ProtocolConstants::METHOD_TOOLS_CALL, $params, $requestId);
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
            ProtocolConstants::METHOD_RESOURCES_LIST,
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

        return JsonRpcMessage::createRequest(ProtocolConstants::METHOD_RESOURCES_READ, $params, $requestId);
    }

    /**
     * Create a resources/subscribe request.
     *
     * @param int|string $requestId Request ID
     * @param string $uri Resource URI to subscribe to
     */
    public static function createSubscribeRequest($requestId, string $uri): JsonRpcMessage
    {
        $params = ['uri' => $uri];

        return JsonRpcMessage::createRequest(ProtocolConstants::METHOD_RESOURCES_SUBSCRIBE, $params, $requestId);
    }

    /**
     * Create a resources/unsubscribe request.
     *
     * @param int|string $requestId Request ID
     * @param string $uri Resource URI to unsubscribe from
     */
    public static function createUnsubscribeRequest($requestId, string $uri): JsonRpcMessage
    {
        $params = ['uri' => $uri];

        return JsonRpcMessage::createRequest(ProtocolConstants::METHOD_RESOURCES_UNSUBSCRIBE, $params, $requestId);
    }

    /**
     * Create a prompts/list request.
     *
     * @param int|string $requestId Request ID
     * @param null|string $cursor Optional cursor for pagination
     */
    public static function createListPromptsRequest($requestId, ?string $cursor = null): JsonRpcMessage
    {
        $params = [];
        if ($cursor !== null) {
            $params['cursor'] = $cursor;
        }

        return JsonRpcMessage::createRequest(
            ProtocolConstants::METHOD_PROMPTS_LIST,
            empty($params) ? null : $params,
            $requestId
        );
    }

    /**
     * Create a prompts/get request.
     *
     * @param int|string $requestId Request ID
     * @param string $name Prompt name
     * @param array<string, string> $arguments Prompt arguments
     */
    public static function createGetPromptRequest($requestId, string $name, array $arguments = []): JsonRpcMessage
    {
        $params = ['name' => $name];
        if (! empty($arguments)) {
            $params['arguments'] = $arguments;
        }

        return JsonRpcMessage::createRequest(ProtocolConstants::METHOD_PROMPTS_GET, $params, $requestId);
    }

    /**
     * Create a cancelled notification.
     *
     * @param int|string $requestId The ID of the request being cancelled
     * @param null|string $reason Optional reason for cancellation
     */
    public static function createCancelledNotification($requestId, ?string $reason = null): JsonRpcMessage
    {
        $params = ['requestId' => $requestId];
        if ($reason !== null) {
            $params['reason'] = $reason;
        }

        return JsonRpcMessage::createNotification(ProtocolConstants::NOTIFICATION_CANCELLED, $params);
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

        return JsonRpcMessage::createNotification(ProtocolConstants::NOTIFICATION_PROGRESS, $params);
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

        return JsonRpcMessage::createNotification(ProtocolConstants::NOTIFICATION_MESSAGE, $params);
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
            ProtocolConstants::METHOD_INITIALIZE,
            ProtocolConstants::NOTIFICATION_INITIALIZED,
            ProtocolConstants::METHOD_PING,
            ProtocolConstants::METHOD_TOOLS_LIST,
            ProtocolConstants::METHOD_TOOLS_CALL,
            ProtocolConstants::METHOD_RESOURCES_LIST,
            ProtocolConstants::METHOD_RESOURCES_READ,
            ProtocolConstants::METHOD_RESOURCES_SUBSCRIBE,
            ProtocolConstants::METHOD_RESOURCES_UNSUBSCRIBE,
            ProtocolConstants::METHOD_PROMPTS_LIST,
            ProtocolConstants::METHOD_PROMPTS_GET,
            ProtocolConstants::METHOD_SAMPLING_CREATE_MESSAGE,
            ProtocolConstants::METHOD_ROOTS_LIST,
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

    /**
     * Create a tools list response.
     *
     * @param int|string $requestId Request ID being responded to
     * @param array<int, array<string, mixed>> $tools Array of tool definitions
     * @return JsonRpcMessage The tools list response
     */
    public static function createToolsListResponse($requestId, array $tools): JsonRpcMessage
    {
        return JsonRpcMessage::createResponse($requestId, ['tools' => $tools]);
    }

    /**
     * Create a tools call response.
     *
     * @param int|string $requestId Request ID being responded to
     * @param mixed $result The result from tool execution
     * @return JsonRpcMessage The tools call response
     */
    public static function createToolsCallResponse($requestId, $result): JsonRpcMessage
    {
        $content = [
            'content' => [
                [
                    'type' => 'text',
                    'text' => is_string($result) ? $result : json_encode($result),
                ],
            ],
        ];
        return JsonRpcMessage::createResponse($requestId, $content);
    }

    /**
     * Create a prompts list response.
     *
     * @param int|string $requestId Request ID being responded to
     * @param array<int, array<string, mixed>> $prompts Array of prompt definitions
     * @return JsonRpcMessage The prompts list response
     */
    public static function createPromptsListResponse($requestId, array $prompts): JsonRpcMessage
    {
        return JsonRpcMessage::createResponse($requestId, ['prompts' => $prompts]);
    }

    /**
     * Create a prompts get response.
     *
     * @param int|string $requestId Request ID being responded to
     * @param array<string, mixed> $promptResult The result from prompt execution
     * @return JsonRpcMessage The prompts get response
     */
    public static function createPromptsGetResponse($requestId, array $promptResult): JsonRpcMessage
    {
        return JsonRpcMessage::createResponse($requestId, $promptResult);
    }

    /**
     * Create a resources list response.
     *
     * @param int|string $requestId Request ID being responded to
     * @param array<int, array<string, mixed>> $resources Array of resource definitions
     * @return JsonRpcMessage The resources list response
     */
    public static function createResourcesListResponse($requestId, array $resources): JsonRpcMessage
    {
        return JsonRpcMessage::createResponse($requestId, ['resources' => $resources]);
    }

    /**
     * Create a resources read response.
     *
     * @param int|string $requestId Request ID being responded to
     * @param array<string, mixed> $content The resource content
     * @return JsonRpcMessage The resources read response
     */
    public static function createResourcesReadResponse($requestId, array $content): JsonRpcMessage
    {
        return JsonRpcMessage::createResponse($requestId, ['contents' => [$content]]);
    }
}
