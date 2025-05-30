<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core;

use Dtyq\PhpMcp\Server\Transports\Core\Handlers\MessageHandlerInterface;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\NotificationHandlerInterface;
use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Shared\Message\JsonRpcMessage;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use Exception;
use stdClass;
use Throwable;

/**
 * Core message processor for handling JSON-RPC messages.
 *
 * This class focuses purely on business logic - routing messages
 * to appropriate handlers and generating responses. Message validation
 * is handled at the transport layer.
 */
class MessageProcessor
{
    private Application $application;

    private LoggerProxy $logger;

    private TransportMetadata $transportMetadata;

    private HandlerFactory $handlerFactory;

    public function __construct(Application $app, TransportMetadata $transportMetadata)
    {
        $this->application = $app;
        $this->transportMetadata = $transportMetadata;
        $this->logger = $app->getLogger();
        $this->handlerFactory = new HandlerFactory();
    }

    /**
     * Process a JSON-RPC message.
     *
     * Note: Message validation should be done at the transport layer.
     * This method assumes the message is already validated.
     *
     * @param string $jsonRpc Pre-validated JSON-RPC message string
     * @return null|string The response JSON string or null
     * @throws TransportError If processing fails
     */
    public function processJsonRpc(string $jsonRpc): ?string
    {
        try {
            // Parse JSON using JsonUtils for better error handling
            $decoded = JsonUtils::decode($jsonRpc, true);

            if (is_array($decoded) && array_is_list($decoded)) {
                throw new TransportError(
                    'Batch processing is not supported',
                    ErrorCodes::INVALID_REQUEST
                );
            }

            return $this->handleSingleMessage($decoded);
        } catch (Exception $e) {
            $this->logger->error('Message processing failed', [
                'message' => $jsonRpc,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a single JSON-RPC message.
     *
     * @param array<string, mixed> $decoded The decoded message array
     * @return null|string The response JSON string or null
     */
    protected function handleSingleMessage(array $decoded): ?string
    {
        $method = $decoded['method'] ?? '';
        $id = $decoded['id'] ?? null;
        $response = null;
        try {
            $handler = $this->handlerFactory->createHandler($this->application, $method);
            if (! $handler) {
                return null;
            }
            $result = null;
            if ($handler instanceof NotificationHandlerInterface) {
                $result = $handler->handle($handler->createNotification($decoded), $this->transportMetadata);
            }
            if ($handler instanceof MessageHandlerInterface) {
                $result = $handler->handle($handler->createRequest($decoded), $this->transportMetadata);
            }

            if ($result) {
                $response = JsonRpcMessage::createResponse($id, $result->toArray());
            }
        } catch (Throwable $e) {
            $this->logger->error('Request handling failed', [
                'method' => $method,
                'params' => $decoded,
                'error' => $e->getMessage(),
            ]);

            $response = JsonRpcMessage::createError(
                $id,
                [
                    'code' => ErrorCodes::INTERNAL_ERROR,
                    'message' => $e->getMessage(),
                    'data' => new stdClass(),
                ],
            );
        }
        return $response ? $response->toJson() : null;
    }
}
