<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Constants;

/**
 * MCP method and notification constants.
 *
 * Contains all method names and notification types defined
 * in the Model Context Protocol specification.
 */
final class MethodConstants
{
    // Core MCP methods
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
     * Get all resource-related methods.
     *
     * @return array<string>
     */
    public static function getResourceMethods(): array
    {
        return [
            self::METHOD_RESOURCES_LIST,
            self::METHOD_RESOURCES_TEMPLATES_LIST,
            self::METHOD_RESOURCES_READ,
            self::METHOD_RESOURCES_SUBSCRIBE,
            self::METHOD_RESOURCES_UNSUBSCRIBE,
        ];
    }

    /**
     * Get all tool-related methods.
     *
     * @return array<string>
     */
    public static function getToolMethods(): array
    {
        return [
            self::METHOD_TOOLS_LIST,
            self::METHOD_TOOLS_CALL,
        ];
    }

    /**
     * Get all prompt-related methods.
     *
     * @return array<string>
     */
    public static function getPromptMethods(): array
    {
        return [
            self::METHOD_PROMPTS_LIST,
            self::METHOD_PROMPTS_GET,
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
     * Check if a method is a notification.
     */
    public static function isNotification(string $method): bool
    {
        return in_array($method, self::getSupportedNotifications(), true);
    }

    /**
     * Check if a method requires authentication.
     */
    public static function requiresAuthentication(string $method): bool
    {
        // Most methods require authentication except ping and initialize
        return ! in_array($method, [
            self::METHOD_INITIALIZE,
            self::METHOD_PING,
        ], true);
    }
}
