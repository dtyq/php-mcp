<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Core;

use Dtyq\PhpMcp\Client\Configuration\ClientConfig;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;

/**
 * Main interface for MCP client implementations.
 *
 * This interface defines the contract for connecting to MCP servers,
 * managing sessions, and handling the client lifecycle.
 */
interface ClientInterface
{
    /**
     * Connect to the MCP server using the configured transport.
     *
     * This method establishes the transport connection and initializes
     * the session with the server.
     *
     * @throws TransportError If transport connection fails
     * @throws ProtocolError If protocol handshake fails
     */
    public function connect(): void;

    /**
     * Get the active session for communicating with the server.
     *
     * The session is available after a successful connection and
     * can be used to send requests and notifications.
     *
     * @return SessionInterface The active session
     * @throws ProtocolError If not connected or session not initialized
     */
    public function getSession(): SessionInterface;

    /**
     * Disconnect from the MCP server.
     *
     * This method gracefully closes the session and transport connection,
     * cleaning up all resources.
     */
    public function disconnect(): void;

    /**
     * Check if the client is currently connected to the server.
     *
     * @return bool True if connected and session is active
     */
    public function isConnected(): bool;

    /**
     * Get the client configuration.
     *
     * @return ClientConfig The configuration used by this client
     */
    public function getConfig(): ClientConfig;
}
