<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Server\Transports\Core\TransportMetadata;
use Dtyq\PhpMcp\Types\Core\NotificationInterface;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Notifications\CancelledNotification;

/**
 * Handler for MCP Cancelled notifications.
 */
class CancelledNotificationMessageHandler extends AbstractNotificationHandler
{
    /**
     * @param array<string, mixed> $request
     */
    public function createNotification(array $request): NotificationInterface
    {
        return CancelledNotification::fromArray($request);
    }

    public function handle(NotificationInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        // Cancelled notifications don't require a response
        // Just log or handle the cancellation internally
        return null;
    }
}
