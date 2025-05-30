<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core\Handlers;

use Dtyq\PhpMcp\Server\Transports\TransportMetadata;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use Dtyq\PhpMcp\Types\Core\ResultInterface;
use Dtyq\PhpMcp\Types\Requests\InitializeRequest;
use Dtyq\PhpMcp\Types\Responses\InitializeResult;
use stdClass;

/**
 * Handler for MCP Initialize requests.
 */
class InitializeMessageHandler extends AbstractMessageHandler
{
    /**
     * @param array<string, mixed> $request
     */
    public function createRequest(array $request): RequestInterface
    {
        return InitializeRequest::fromArray($request);
    }

    public function handle(RequestInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        $capabilities = [
            'instructions' => $metadata->getInstructions() ?: '',
            'logging' => new stdClass(),
        ];

        if ($metadata->getToolManager()->count() > 0) {
            $capabilities['tools'] = [
                'listChanged' => false,
            ];
        }

        if ($metadata->getPromptManager()->count() > 0) {
            $capabilities['prompts'] = [
                'listChanged' => false,
            ];
        }

        if ($metadata->getResourceManager()->count() > 0) {
            $capabilities['resources'] = [
                'listChanged' => false,
            ];
        }

        $serverInfo = [
            'name' => $metadata->getName() ?: 'php-mcp-server',
            'version' => $metadata->getVersion() ?: '1.0.0',
        ];

        return new InitializeResult(
            ProtocolConstants::LATEST_PROTOCOL_VERSION,
            $capabilities,
            $serverInfo,
            $metadata->getInstructions()
        );
    }
}
