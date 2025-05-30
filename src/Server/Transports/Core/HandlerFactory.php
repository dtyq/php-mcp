<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core;

use Dtyq\PhpMcp\Server\Transports\Core\Handlers\CallToolMessageHandler;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\CancelledNotificationMessageHandler;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\GetPromptMessageHandler;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\InitializedNotificationMessageHandler;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\InitializeMessageHandler;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\ListPromptsMessageHandler;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\ListResourcesMessageHandler;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\ListResourceTemplatesMessageHandler;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\ListToolsMessageHandler;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\MessageHandlerInterface;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\NotificationHandlerInterface;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\PingMessageHandler;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\ProgressNotificationMessageHandler;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\ReadResourceMessageHandler;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;

/**
 * Unified factory for creating and managing message handlers.
 *
 * Uses the Factory pattern to provide appropriate handlers for different message types,
 * supporting both requests (with responses) and notifications (fire-and-forget).
 *
 * Based on MCP Protocol Revision: 2025-03-26
 */
class HandlerFactory
{
    /** @var array<string, class-string<MessageHandlerInterface|NotificationHandlerInterface>> */
    private array $handlers = [
        // Core MCP protocol methods
        ProtocolConstants::METHOD_INITIALIZE => InitializeMessageHandler::class,
        ProtocolConstants::METHOD_PING => PingMessageHandler::class,

        // Tool-related methods
        ProtocolConstants::METHOD_TOOLS_LIST => ListToolsMessageHandler::class,
        ProtocolConstants::METHOD_TOOLS_CALL => CallToolMessageHandler::class,

        // Prompt-related methods
        ProtocolConstants::METHOD_PROMPTS_LIST => ListPromptsMessageHandler::class,
        ProtocolConstants::METHOD_PROMPTS_GET => GetPromptMessageHandler::class,

        // Resource methods
        ProtocolConstants::METHOD_RESOURCES_LIST => ListResourcesMessageHandler::class,
        ProtocolConstants::METHOD_RESOURCES_TEMPLATES_LIST => ListResourceTemplatesMessageHandler::class,
        ProtocolConstants::METHOD_RESOURCES_READ => ReadResourceMessageHandler::class,

        // Core notification methods
        ProtocolConstants::NOTIFICATION_INITIALIZED => InitializedNotificationMessageHandler::class,
        ProtocolConstants::NOTIFICATION_PROGRESS => ProgressNotificationMessageHandler::class,
        ProtocolConstants::NOTIFICATION_CANCELLED => CancelledNotificationMessageHandler::class,
    ];

    /**
     * @return null|MessageHandlerInterface|NotificationHandlerInterface
     */
    public function createHandler(Application $application, string $method)
    {
        if (! isset($this->handlers[$method])) {
            return null;
        }

        return new $this->handlers[$method]($application);
    }
}
