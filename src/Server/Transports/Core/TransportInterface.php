<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core;

use Dtyq\PhpMcp\Shared\Exceptions\TransportError;

/**
 * Base interface for all MCP transport implementations.
 *
 * This interface defines the common contract that all transport mechanisms
 * (stdio, HTTP, etc.) must implement according to MCP 2025-03-26 specification.
 */
interface TransportInterface
{
    /**
     * Start the transport and begin listening for messages.
     *
     * @throws TransportError If transport cannot be started
     */
    public function start(): void;

    /**
     * Stop the transport and cleanup resources.
     *
     * @throws TransportError If transport cannot be stopped
     */
    public function stop(): void;

    /**
     * Check if the transport is currently running.
     */
    public function isRunning(): bool;

    /**
     * Handle an incoming message and return a response if needed.
     *
     * @param string $message The incoming JSON-RPC message
     * @return null|string The response message, or null if no response needed
     * @throws TransportError If message handling fails
     */
    public function handleMessage(string $message): ?string;

    /**
     * Send a message through the transport.
     *
     * @param string $message The JSON-RPC message to send
     * @throws TransportError If message cannot be sent
     */
    public function sendMessage(string $message): void;
}
