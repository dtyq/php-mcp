<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core;

use Dtyq\PhpMcp\Server\Transports\Core\Handlers\MessageHandlerInterface;
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\NotificationHandlerInterface;
use Dtyq\PhpMcp\Server\Transports\TransportMetadata;
use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Shared\Message\JsonRpcMessage;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Exception;
use stdClass;
use Throwable;

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

    public function processJsonRpc(string $jsonRpc): ?string
    {
        try {
            // Parse JSON using JsonUtils for better error handling
            $decoded = JsonUtils::decode($jsonRpc, true);

            if (is_array($decoded) && isset($decoded[0])) {
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
     * Validate a JSON-RPC message structure.
     *
     * @param string $message The message to validate
     * @return bool True if valid, false otherwise
     */
    public function validateMessage(string $message): bool
    {
        if (! $this->validateUtf8($message)) {
            return false;
        }

        $decoded = json_decode($message, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        // Basic JSON-RPC validation
        if (is_array($decoded)) {
            if (isset($decoded[0])) {
                // Batch
                foreach ($decoded as $item) {
                    if (! $this->validateSingleMessage($item)) {
                        return false;
                    }
                }
            } else {
                // Single message
                return $this->validateSingleMessage($decoded);
            }
        }

        return true;
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

    /**
     * Validate that a message is valid UTF-8.
     *
     * @param string $message The message to validate
     * @return bool True if valid UTF-8, false otherwise
     */
    private function validateUtf8(string $message): bool
    {
        return mb_check_encoding($message, 'UTF-8');
    }

    /**
     * Validate a single JSON-RPC message.
     *
     * @param array<string, mixed> $message The message to validate
     * @return bool True if valid, false otherwise
     */
    private function validateSingleMessage(array $message): bool
    {
        // Must have jsonrpc version
        if (! isset($message['jsonrpc']) || $message['jsonrpc'] !== ProtocolConstants::JSONRPC_VERSION) {
            return false;
        }

        // Request or notification must have method
        if (isset($message['method'])) {
            return is_string($message['method']);
        }

        // Response must have result or error, and id
        if (isset($message['result']) || isset($message['error'])) {
            return isset($message['id']);
        }

        return false;
    }
}
