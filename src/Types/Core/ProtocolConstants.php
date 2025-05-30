<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Core;

/**
 * MCP Protocol constants and definitions.
 *
 * Contains version information, error codes, and common constants
 * used throughout the Model Context Protocol implementation.
 */
final class ProtocolConstants
{
    /** Current MCP protocol version */
    public const LATEST_PROTOCOL_VERSION = '2025-03-26';

    /** JSON-RPC 2.0 version */
    public const JSONRPC_VERSION = '2.0';

    // Standard JSON-RPC error codes
    public const PARSE_ERROR = -32700;

    public const INVALID_REQUEST = -32600;

    public const METHOD_NOT_FOUND = -32601;

    public const INVALID_PARAMS = -32602;

    public const INTERNAL_ERROR = -32603;

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

    // Message roles
    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    // Content types
    public const CONTENT_TYPE_TEXT = 'text';

    public const CONTENT_TYPE_IMAGE = 'image';

    public const CONTENT_TYPE_RESOURCE = 'resource';

    // Stop reasons for sampling
    public const STOP_REASON_END_TURN = 'endTurn';

    public const STOP_REASON_MAX_TOKENS = 'maxTokens';

    public const STOP_REASON_STOP_SEQUENCE = 'stopSequence';

    public const STOP_REASON_TOOL_USE = 'toolUse';

    // Reference types
    public const REF_TYPE_RESOURCE = 'ref/resource';

    public const REF_TYPE_PROMPT = 'ref/prompt';

    // Logging levels
    public const LOG_LEVEL_DEBUG = 'debug';

    public const LOG_LEVEL_INFO = 'info';

    public const LOG_LEVEL_NOTICE = 'notice';

    public const LOG_LEVEL_WARNING = 'warning';

    public const LOG_LEVEL_ERROR = 'error';

    public const LOG_LEVEL_CRITICAL = 'critical';

    public const LOG_LEVEL_ALERT = 'alert';

    public const LOG_LEVEL_EMERGENCY = 'emergency';

    // MCP method names
    public const METHOD_INITIALIZE = 'initialize';

    public const METHOD_PING = 'ping';

    // Resource methods
    public const METHOD_RESOURCES_LIST = 'resources/list';

    public const METHOD_RESOURCES_TEMPLATES_LIST = 'resources/templates/list';

    public const METHOD_RESOURCES_READ = 'resources/read';

    public const METHOD_RESOURCES_SUBSCRIBE = 'resources/subscribe';

    public const METHOD_RESOURCES_UNSUBSCRIBE = 'resources/unsubscribe';

    // Tool methods
    public const METHOD_TOOLS_LIST = 'tools/list';

    public const METHOD_TOOLS_CALL = 'tools/call';

    // Prompt methods
    public const METHOD_PROMPTS_LIST = 'prompts/list';

    public const METHOD_PROMPTS_GET = 'prompts/get';

    // Sampling methods
    public const METHOD_SAMPLING_CREATE_MESSAGE = 'sampling/createMessage';

    // Completion methods
    public const METHOD_COMPLETION_COMPLETE = 'completion/complete';

    // Roots methods
    public const METHOD_ROOTS_LIST = 'roots/list';

    // Logging methods
    public const METHOD_LOGGING_SET_LEVEL = 'logging/setLevel';

    // Notification methods
    public const NOTIFICATION_INITIALIZED = 'notifications/initialized';

    public const NOTIFICATION_PROGRESS = 'notifications/progress';

    public const NOTIFICATION_CANCELLED = 'notifications/cancelled';

    public const NOTIFICATION_MESSAGE = 'notifications/message';

    public const NOTIFICATION_RESOURCES_LIST_CHANGED = 'notifications/resources/list_changed';

    public const NOTIFICATION_RESOURCES_UPDATED = 'notifications/resources/updated';

    public const NOTIFICATION_TOOLS_LIST_CHANGED = 'notifications/tools/list_changed';

    public const NOTIFICATION_PROMPTS_LIST_CHANGED = 'notifications/prompts/list_changed';

    public const NOTIFICATION_ROOTS_LIST_CHANGED = 'notifications/roots/list_changed';

    // MIME types
    public const MIME_TYPE_TEXT_PLAIN = 'text/plain';

    public const MIME_TYPE_TEXT_HTML = 'text/html';

    public const MIME_TYPE_TEXT_MARKDOWN = 'text/markdown';

    public const MIME_TYPE_APPLICATION_JSON = 'application/json';

    public const MIME_TYPE_IMAGE_PNG = 'image/png';

    public const MIME_TYPE_IMAGE_JPEG = 'image/jpeg';

    public const MIME_TYPE_IMAGE_GIF = 'image/gif';

    public const MIME_TYPE_IMAGE_WEBP = 'image/webp';

    // Transport types
    public const TRANSPORT_TYPE_STDIO = 'stdio';

    public const TRANSPORT_TYPE_HTTP = 'http';

    public const TRANSPORT_TYPE_SSE = 'sse';

    public const TRANSPORT_TYPE_WEBSOCKET = 'websocket';

    /**
     * Get all supported MCP methods.
     *
     * @return array<string>
     */
    public static function getSupportedMethods(): array
    {
        return [
            self::METHOD_INITIALIZE,
            self::METHOD_PING,
            self::METHOD_RESOURCES_LIST,
            self::METHOD_RESOURCES_TEMPLATES_LIST,
            self::METHOD_RESOURCES_READ,
            self::METHOD_RESOURCES_SUBSCRIBE,
            self::METHOD_RESOURCES_UNSUBSCRIBE,
            self::METHOD_TOOLS_LIST,
            self::METHOD_TOOLS_CALL,
            self::METHOD_PROMPTS_LIST,
            self::METHOD_PROMPTS_GET,
            self::METHOD_SAMPLING_CREATE_MESSAGE,
            self::METHOD_COMPLETION_COMPLETE,
            self::METHOD_ROOTS_LIST,
            self::METHOD_LOGGING_SET_LEVEL,
        ];
    }

    /**
     * Get all supported notification methods.
     *
     * @return array<string>
     */
    public static function getSupportedNotifications(): array
    {
        return [
            self::NOTIFICATION_INITIALIZED,
            self::NOTIFICATION_PROGRESS,
            self::NOTIFICATION_CANCELLED,
            self::NOTIFICATION_MESSAGE,
            self::NOTIFICATION_RESOURCES_LIST_CHANGED,
            self::NOTIFICATION_RESOURCES_UPDATED,
            self::NOTIFICATION_TOOLS_LIST_CHANGED,
            self::NOTIFICATION_PROMPTS_LIST_CHANGED,
            self::NOTIFICATION_ROOTS_LIST_CHANGED,
        ];
    }

    /**
     * Get all valid roles.
     *
     * @return array<string>
     */
    public static function getValidRoles(): array
    {
        return [
            self::ROLE_USER,
            self::ROLE_ASSISTANT,
        ];
    }

    /**
     * Get all valid logging levels.
     *
     * @return array<string>
     */
    public static function getValidLogLevels(): array
    {
        return [
            self::LOG_LEVEL_DEBUG,
            self::LOG_LEVEL_INFO,
            self::LOG_LEVEL_NOTICE,
            self::LOG_LEVEL_WARNING,
            self::LOG_LEVEL_ERROR,
            self::LOG_LEVEL_CRITICAL,
            self::LOG_LEVEL_ALERT,
            self::LOG_LEVEL_EMERGENCY,
        ];
    }

    /**
     * Check if a method is supported.
     */
    public static function isValidMethod(string $method): bool
    {
        return in_array($method, self::getSupportedMethods(), true)
               || in_array($method, self::getSupportedNotifications(), true);
    }

    /**
     * Check if a role is valid.
     */
    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::getValidRoles(), true);
    }

    /**
     * Check if a logging level is valid.
     */
    public static function isValidLogLevel(string $level): bool
    {
        return in_array($level, self::getValidLogLevels(), true);
    }

    /**
     * Get all supported transport types.
     *
     * @return array<string>
     */
    public static function getSupportedTransportTypes(): array
    {
        return [
            self::TRANSPORT_TYPE_STDIO,
            self::TRANSPORT_TYPE_HTTP,
            self::TRANSPORT_TYPE_SSE,
            self::TRANSPORT_TYPE_WEBSOCKET,
        ];
    }

    /**
     * Check if a transport type is supported.
     */
    public static function isValidTransportType(string $transportType): bool
    {
        return in_array($transportType, self::getSupportedTransportTypes(), true);
    }
}
