<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Server\Transports\TransportMetadata;
use Dtyq\PhpMcp\Types\Core\NotificationInterface;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Notifications\ProgressNotification;

/**
 * Handler for MCP Progress notifications.
 */
class ProgressNotificationMessageHandler extends AbstractNotificationHandler
{
    /**
     * @param array<string, mixed> $request
     */
    public function createNotification(array $request): NotificationInterface
    {
        return ProgressNotification::fromArray($request);
    }

    public function handle(NotificationInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        // Progress notifications don't require a response
        // Just log or handle the progress update internally
        return null;
    }
}
