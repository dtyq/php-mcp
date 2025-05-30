<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Core;

use Dtyq\PhpMcp\Shared\Exceptions\TransportError;

/**
 * Interface for MCP transport implementations.
 *
 * This interface defines the contract for different transport mechanisms
 * (stdio, HTTP, WebSocket, etc.) used to communicate with MCP servers.
 */
interface TransportInterface
{
    /**
     * Connect to the MCP server.
     *
     * This method establishes the underlying transport connection
     * and prepares it for message exchange.
     *
     * @throws TransportError If connection fails
     */
    public function connect(): void;

    /**
     * Send a message to the server.
     *
     * This method sends a JSON-RPC message through the transport
     * and handles transport-specific encoding/formatting.
     *
     * @param string $message The JSON-RPC message to send
     * @throws TransportError If sending fails
     */
    public function send(string $message): void;

    /**
     * Receive a message from the server.
     *
     * This method waits for and receives a message from the server,
     * handling transport-specific decoding and validation.
     *
     * @param null|int $timeout Timeout in seconds (null for default)
     * @return string The received JSON-RPC message
     * @throws TransportError If receiving fails or times out
     */
    public function receive(?int $timeout = null): string;

    /**
     * Check if the transport is currently connected.
     *
     * @return bool True if connected and ready for communication
     */
    public function isConnected(): bool;

    /**
     * Disconnect from the server.
     *
     * This method gracefully closes the transport connection
     * and cleans up any associated resources.
     *
     * @throws TransportError If disconnection fails
     */
    public function disconnect(): void;

    /**
     * Get the transport type identifier.
     *
     * This method returns a string identifier for the transport type
     * (e.g., "stdio", "http", "websocket") for logging and debugging.
     *
     * @return string The transport type identifier
     */
    public function getType(): string;
}
