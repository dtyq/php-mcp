<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Types\Core\NotificationInterface;
use Dtyq\PhpMcp\Types\Notifications\InitializedNotification;

/**
 * Handler for MCP Initialized notifications.
 */
class InitializedNotificationMessageHandler extends AbstractNotificationHandler
{
    /**
     * @param array<string, mixed> $request
     */
    public function createNotification(array $request): NotificationInterface
    {
        return InitializedNotification::fromArray($request);
    }
}
