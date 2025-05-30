<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Server\Transports\TransportMetadata;
use Dtyq\PhpMcp\Types\Core\NotificationInterface;
use Dtyq\PhpMcp\Types\Core\ResultInterface;

interface NotificationHandlerInterface
{
    /**
     * @param array<string, mixed> $request
     */
    public function createNotification(array $request): NotificationInterface;

    public function handle(NotificationInterface $message, TransportMetadata $metadata): ?ResultInterface;
}
