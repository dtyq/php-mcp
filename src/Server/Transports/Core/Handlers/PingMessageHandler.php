<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Server\Transports\TransportMetadata;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Requests\PingRequest;
use Dtyq\PhpMcp\Types\Responses\PingResult;

/**
 * Handler for MCP Ping requests.
 */
class PingMessageHandler extends AbstractMessageHandler
{
    /**
     * @param array<string, mixed> $request
     */
    public function createRequest(array $request): RequestInterface
    {
        return PingRequest::fromArray($request);
    }

    public function handle(RequestInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        // Ping responds with an empty object (pong)
        return new PingResult();
    }
}
