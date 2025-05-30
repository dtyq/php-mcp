<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Server\Transports\Core\TransportMetadata;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Requests\ListResourcesRequest;
use Dtyq\PhpMcp\Types\Responses\ListResourcesResult;

/**
 * Handler for MCP List Resources requests.
 */
class ListResourcesMessageHandler extends AbstractMessageHandler
{
    /**
     * @param array<string, mixed> $request
     */
    public function createRequest(array $request): RequestInterface
    {
        return ListResourcesRequest::fromArray($request);
    }

    public function handle(RequestInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        $resources = $metadata->getResourceManager()->getAll();
        $resourceObjects = array_map(function ($registeredResource) {
            return $registeredResource->getResource();
        }, $resources);

        // Use ListResourcesResult for type safety
        return new ListResourcesResult($resourceObjects);
    }
}
