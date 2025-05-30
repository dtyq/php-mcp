<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Core;

use Dtyq\PhpMcp\Client\Configuration\ClientConfig;
use Dtyq\PhpMcp\Client\Transport\TransportFactory;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Exception;

/**
 * Abstract base class for MCP client implementations.
 *
 * This class provides common functionality and utilities that all client
 * implementations can use, ensuring consistency across different client types.
 */
abstract class AbstractClient implements ClientInterface
{
    protected ClientConfig $config;

    protected ?TransportInterface $transport = null;

    protected ?SessionInterface $session = null;

    protected bool $connected = false;

    public function __construct(ClientConfig $config)
    {
        $this->config = $config;
    }

    public function connect(): void
    {
        if ($this->connected) {
            throw ProtocolError::invalidState(
                'connect',
                'connected',
                'disconnected'
            );
        }

        try {
            // Create transport using factory
            $this->transport = TransportFactory::create(
                $this->config->getTransportType(),
                $this->config
            );

            // Connect transport
            $this->transport->connect();

            // Create session
            $this->session = $this->createSession($this->transport);

            $this->connected = true;
        } catch (TransportError $e) {
            $this->cleanup();
            throw $e;
        } catch (Exception $e) {
            $this->cleanup();
            throw new TransportError('Failed to establish connection: ' . $e->getMessage());
        }
    }

    public function getSession(): SessionInterface
    {
        if (! $this->connected || $this->session === null) {
            throw ProtocolError::invalidState(
                'getSession',
                'disconnected',
                'connected'
            );
        }

        return $this->session;
    }

    public function disconnect(): void
    {
        if (! $this->connected) {
            return; // Already disconnected
        }

        $this->cleanup();
    }

    public function isConnected(): bool
    {
        return $this->connected
            && $this->transport !== null
            && $this->transport->isConnected();
    }

    public function getConfig(): ClientConfig
    {
        return $this->config;
    }

    /**
     * Create a session instance for the given transport.
     *
     * This method must be implemented by concrete client classes
     * to provide transport-specific session implementations.
     *
     * @param TransportInterface $transport The connected transport
     * @return SessionInterface The created session
     */
    abstract protected function createSession(TransportInterface $transport): SessionInterface;

    /**
     * Cleanup resources and reset connection state.
     */
    protected function cleanup(): void
    {
        try {
            if ($this->session !== null) {
                $this->session->close();
                $this->session = null;
            }
        } catch (Exception $e) {
            // Log but don't throw during cleanup
            // TODO: Add logging when logger is available
        }

        try {
            if ($this->transport !== null) {
                $this->transport->disconnect();
                $this->transport = null;
            }
        } catch (Exception $e) {
            // Log but don't throw during cleanup
            // TODO: Add logging when logger is available
        }

        $this->connected = false;
    }

    /**
     * Get the current transport instance.
     *
     * @return null|TransportInterface The transport or null if not connected
     */
    protected function getTransport(): ?TransportInterface
    {
        return $this->transport;
    }

    /**
     * Check if the client configuration is valid for the current operation.
     *
     * @throws ProtocolError If configuration is invalid
     */
    protected function validateConfiguration(): void
    {
        try {
            $this->config->validate();
        } catch (Exception $e) {
            throw new ProtocolError('Invalid configuration: ' . $e->getMessage());
        }
    }

    /**
     * Handle connection errors during operations.
     *
     * @param Exception $error The error that occurred
     * @param string $operation The operation that was being performed
     * @throws TransportError
     */
    protected function handleConnectionError(Exception $error, string $operation): void
    {
        // Mark as disconnected if transport error occurs
        if ($error instanceof TransportError) {
            $this->connected = false;
        }

        throw new TransportError(
            "Connection error during {$operation}: " . $error->getMessage(),
            ['operation' => $operation, 'original_error' => $error->getMessage()]
        );
    }
}
