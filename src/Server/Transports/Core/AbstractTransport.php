<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core;

use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Types\Core\MessageValidator;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Exception;

/**
 * Abstract base class for all MCP transport implementations.
 *
 * Provides common functionality and utilities that all transport
 * implementations can use, ensuring consistency across different
 * transport mechanisms.
 */
abstract class AbstractTransport implements TransportInterface
{
    protected Application $app;

    protected MessageProcessor $processor;

    protected LoggerProxy $logger;

    protected bool $running = false;

    public function __construct(Application $app, TransportMetadata $transportMetadata)
    {
        $this->app = $app;
        $this->processor = new MessageProcessor($app, $transportMetadata);
        $this->logger = $app->getLogger();
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function handleMessage(string $message): ?string
    {
        try {
            // Comprehensive message validation at transport layer
            $this->validateMessage($message);

            // Process through message processor (no validation needed)
            return $this->processor->processJsonRpc($message);
        } catch (Exception $e) {
            $this->logger->error('Message handling failed', [
                'transport' => $this->getTransportType(),
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the message processor instance.
     */
    public function getMessageProcessor(): MessageProcessor
    {
        return $this->processor;
    }

    /**
     * Get the application instance.
     */
    public function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * Get the logger instance.
     */
    public function getLogger(): LoggerProxy
    {
        return $this->logger;
    }

    /**
     * Comprehensive message validation at transport layer.
     *
     * This method validates the message format, encoding, and structure
     * before passing it to the message processor.
     *
     * @param string $message The message to validate
     * @throws TransportError If message validation fails
     */
    protected function validateMessage(string $message): void
    {
        try {
            // Use MessageValidator for comprehensive validation
            // Apply strict stdio validation for stdio transports
            $strictMode = $this->getTransportType() === ProtocolConstants::TRANSPORT_TYPE_STDIO;
            MessageValidator::validateMessage($message, $strictMode);
        } catch (ValidationError $e) {
            // Convert ValidationError to TransportError for consistency
            throw TransportError::malformedMessage(
                $this->getTransportType(),
                'Message validation failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get the transport type name for logging and error reporting.
     *
     * @return string The transport type name
     */
    abstract protected function getTransportType(): string;
}
