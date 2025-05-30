<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core;

use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
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
            // Validate message format
            if (! $this->validateJsonRpc($message)) {
                throw TransportError::malformedMessage(
                    $this->getTransportType(),
                    'Invalid JSON-RPC message format'
                );
            }

            // Ensure UTF-8 encoding
            $message = $this->ensureUtf8Encoding($message);

            // Process through message processor
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
     * Validate JSON-RPC message format.
     *
     * @param string $message The message to validate
     * @return bool True if valid, false otherwise
     */
    protected function validateJsonRpc(string $message): bool
    {
        return $this->processor->validateMessage($message);
    }

    /**
     * Ensure message is properly UTF-8 encoded.
     *
     * @param string $message The message to check/convert
     * @return string The UTF-8 encoded message
     * @throws TransportError If message cannot be converted to UTF-8
     */
    protected function ensureUtf8Encoding(string $message): string
    {
        if (! mb_check_encoding($message, 'UTF-8')) {
            // Try to detect and convert encoding
            $detected = mb_detect_encoding($message, ['UTF-8', 'ISO-8859-1', 'ASCII'], true);
            if ($detected && $detected !== 'UTF-8') {
                $converted = mb_convert_encoding($message, 'UTF-8', $detected);
                if ($converted !== false) {
                    $this->logger->warning('Message encoding converted', [
                        'from' => $detected,
                        'to' => 'UTF-8',
                    ]);
                    return $converted;
                }
            }

            throw TransportError::encodingError(
                'UTF-8 validation',
                'Message is not valid UTF-8 and cannot be converted'
            );
        }

        return $message;
    }

    /**
     * Get the transport type name for logging and error reporting.
     *
     * @return string The transport type name
     */
    abstract protected function getTransportType(): string;
}
