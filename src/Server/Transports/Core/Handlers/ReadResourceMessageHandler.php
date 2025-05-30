<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Server\Transports\TransportMetadata;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Requests\ReadResourceRequest;
use Dtyq\PhpMcp\Types\Responses\ReadResourceResult;

/**
 * Handler for MCP Read Resource requests.
 */
class ReadResourceMessageHandler extends AbstractMessageHandler
{
    /**
     * @param array<string, mixed> $request
     */
    public function createRequest(array $request): RequestInterface
    {
        return ReadResourceRequest::fromArray($request);
    }

    public function handle(RequestInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        /** @var ReadResourceRequest $message */
        $uri = $message->getUri();
        $contents = $metadata->getResourceManager()->getContent($uri);

        // Use ReadResourceResult for type safety
        return new ReadResourceResult([$contents]);
    }
} 