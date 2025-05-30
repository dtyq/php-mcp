<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Server\Transports\Core\TransportMetadata;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Core\NotificationInterface;
use Dtyq\PhpMcp\Types\Core\ResultInterface;

abstract class AbstractNotificationHandler implements NotificationHandlerInterface
{
    protected Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function handle(NotificationInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        return null;
    }
}
