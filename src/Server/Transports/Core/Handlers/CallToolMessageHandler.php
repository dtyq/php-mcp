<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Server\Transports\Core\TransportMetadata;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Requests\CallToolRequest;
use Dtyq\PhpMcp\Types\Responses\CallToolResult;

/**
 * Handler for MCP Tool Call requests.
 */
class CallToolMessageHandler extends AbstractMessageHandler
{
    /**
     * @param array<string, mixed> $request
     */
    public function createRequest(array $request): RequestInterface
    {
        return CallToolRequest::fromArray($request);
    }

    public function handle(RequestInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        /** @var CallToolRequest $message */
        $name = $message->getName();
        $arguments = $message->getArguments();
        $result = $metadata->getToolManager()->execute($name, $arguments);

        // Use CallToolResult for type safety
        $textContent = new TextContent(is_string($result) ? $result : JsonUtils::encode($result));
        return new CallToolResult([$textContent], false);
    }
}
