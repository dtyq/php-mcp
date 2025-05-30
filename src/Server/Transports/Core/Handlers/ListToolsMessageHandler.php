<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Server\Transports\TransportMetadata;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Requests\ListToolsRequest;
use Dtyq\PhpMcp\Types\Responses\ListToolsResult;

/**
 * Handler for MCP List Tools requests.
 */
class ListToolsMessageHandler extends AbstractMessageHandler
{
    /**
     * @param array<string, mixed> $request
     */
    public function createRequest(array $request): RequestInterface
    {
        return ListToolsRequest::fromArray($request);
    }

    public function handle(RequestInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        $tools = $metadata->getToolManager()->getAll();
        $toolObjects = array_map(function ($registeredTool) {
            return $registeredTool->getTool();
        }, $tools);

        // Use ListToolsResult for type safety
        return new ListToolsResult($toolObjects);
    }
}
