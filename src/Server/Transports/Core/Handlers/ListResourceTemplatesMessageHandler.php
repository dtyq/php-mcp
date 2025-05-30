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
use Dtyq\PhpMcp\Types\Responses\ListResourceTemplatesResult;

/**
 * Handler for MCP List Resource Templates requests.
 */
class ListResourceTemplatesMessageHandler extends AbstractMessageHandler
{
    /**
     * @param array<string, mixed> $request
     */
    public function createRequest(array $request): RequestInterface
    {
        // Use ListResourcesRequest as base since it has similar structure
        return ListResourcesRequest::fromArray($request);
    }

    public function handle(RequestInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        // Get all registered resource templates
        $templates = $metadata->getResourceManager()->getAllTemplates();
        $templateObjects = array_map(function ($registeredTemplate) {
            return $registeredTemplate->getTemplate();
        }, $templates);

        // Use dedicated ListResourceTemplatesResult for type safety
        return new ListResourceTemplatesResult($templateObjects);
    }
}
