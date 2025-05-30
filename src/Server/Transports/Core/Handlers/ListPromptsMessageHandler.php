<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Server\Transports\TransportMetadata;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Requests\ListPromptsRequest;
use Dtyq\PhpMcp\Types\Responses\ListPromptsResult;

/**
 * Handler for MCP List Prompts requests.
 */
class ListPromptsMessageHandler extends AbstractMessageHandler
{
    /**
     * @param array<string, mixed> $request
     */
    public function createRequest(array $request): RequestInterface
    {
        return ListPromptsRequest::fromArray($request);
    }

    public function handle(RequestInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        $prompts = $metadata->getPromptManager()->getAll();
        $promptObjects = array_map(function ($registeredPrompt) {
            return $registeredPrompt->getPrompt();
        }, $prompts);

        // Use ListPromptsResult for type safety
        return new ListPromptsResult($promptObjects);
    }
}
