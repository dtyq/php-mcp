<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Server\Transports\TransportMetadata;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use Dtyq\PhpMcp\Types\Core\ResultInterface;

interface MessageHandlerInterface
{
    /**
     * @param array<string, mixed> $request
     */
    public function createRequest(array $request): RequestInterface;

    public function handle(RequestInterface $message, TransportMetadata $metadata): ?ResultInterface;
}
